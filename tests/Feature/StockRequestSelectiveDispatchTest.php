<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRequestSelectiveDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $shopAdmin;
    protected Shop $shop;
    protected Item $itemA;
    protected Item $itemB;
    protected Item $itemC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->shop = Shop::create([
            'shop_name' => 'Branch One',
            'location'  => 'Kariakoo',
            'status'    => 'active',
        ]);
        $this->shopAdmin = User::factory()->create([
            'role'    => 'shop_admin',
            'shop_id' => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Accessories']);

        $this->itemA = Item::create(['item_name' => 'Wired Mouse', 'category_id' => $category->id]);
        $this->itemB = Item::create(['item_name' => 'USB Keyboard', 'category_id' => $category->id]);
        $this->itemC = Item::create(['item_name' => 'Finished HDMI Cable', 'category_id' => $category->id]);

        // Item A has 20 in Main Store, Item B has 10, Item C has 0 (finished)
        MainStock::create([
            'item_id'            => $this->itemA->id,
            'buying_price'       => 8000,
            'selling_price'      => 12000,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 20,
            'date_received'      => now()->toDateString(),
        ]);

        MainStock::create([
            'item_id'            => $this->itemB->id,
            'buying_price'       => 15000,
            'selling_price'      => 22000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);
    }

    public function test_shop_admin_create_form_hides_main_store_quantity_and_allows_requesting_out_of_stock_items(): void
    {
        $this->actingAs($this->shopAdmin);

        $page = $this->get(route('stock-requests.create'));
        $page->assertStatus(200);
        $page->assertSee('Wired Mouse');
        $page->assertSee('Finished HDMI Cable');
        $page->assertDontSee('(Stock: 20)');
        $page->assertDontSee('Warehouse Stock Available');

        // Shop Admin submits all 3 items including Finished HDMI Cable (0 in Main Store)
        $res = $this->post(route('stock-requests.store'), [
            'notes' => 'Weekend restock',
            'items' => [
                ['item_id' => $this->itemA->id, 'quantity' => 10],
                ['item_id' => $this->itemB->id, 'quantity' => 5],
                ['item_id' => $this->itemC->id, 'quantity' => 4],
            ],
        ]);

        $res->assertRedirect(route('stock-requests.index'));

        $stockRequest = StockRequest::with('items')->latest('id')->first();
        $this->assertNotNull($stockRequest);
        $this->assertEquals(3, $stockRequest->items->count());
        $this->assertDatabaseHas('stock_request_items', [
            'request_id' => $stockRequest->id,
            'item_id'    => $this->itemC->id,
            'quantity'   => 4,
        ]);
    }

    public function test_owner_can_select_products_regulate_quantities_and_dispatch_directly_excluding_unselected_items(): void
    {
        $stockRequest = StockRequest::create([
            'shop_id'      => $this->shop->id,
            'requested_by' => $this->shopAdmin->id,
            'request_date' => now()->toDateString(),
            'status'       => 'pending',
        ]);

        $reqA = StockRequestItem::create(['request_id' => $stockRequest->id, 'item_id' => $this->itemA->id, 'quantity' => 10]);
        $reqB = StockRequestItem::create(['request_id' => $stockRequest->id, 'item_id' => $this->itemB->id, 'quantity' => 5]);
        $reqC = StockRequestItem::create(['request_id' => $stockRequest->id, 'item_id' => $this->itemC->id, 'quantity' => 4]);

        // Owner selects ONLY Item A (regulated from 10 down to 6) and Item B (kept at 5); Item C is NOT selected
        $response = $this->actingAs($this->owner)
            ->post(route('stock-requests.approve', $stockRequest), [
                'selective_dispatch' => 1,
                'selected_items'     => [$reqA->id, $reqB->id],
                'quantities'         => [
                    $reqA->id => 6,
                    $reqB->id => 5,
                    $reqC->id => 4, // even if passed, unselected item MUST NOT be dispatched
                ],
            ]);

        $response->assertRedirect(route('stock-requests.show', $stockRequest));

        $stockRequest->refresh();
        $this->assertEquals('approved', $stockRequest->status);

        $transfer = StockTransfer::where('request_id', $stockRequest->id)->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('pending_receipt', $transfer->status);

        // Only 2 selected items are in dispatch items with regulated quantities
        $this->assertEquals(2, StockTransferItem::where('transfer_id', $transfer->id)->count());
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $transfer->id,
            'item_id'     => $this->itemA->id,
            'quantity'    => 6,
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $transfer->id,
            'item_id'     => $this->itemB->id,
            'quantity'    => 5,
        ]);
        // Unselected Item C MUST NOT be in dispatch items
        $this->assertDatabaseMissing('stock_transfer_items', [
            'transfer_id' => $transfer->id,
            'item_id'     => $this->itemC->id,
        ]);

        // Main Warehouse stock deducted accurately (20 - 6 = 14, 10 - 5 = 5)
        $this->assertEquals(14, (int) MainStock::where('item_id', $this->itemA->id)->sum('remaining_quantity'));
        $this->assertEquals(5, (int) MainStock::where('item_id', $this->itemB->id)->sum('remaining_quantity'));
    }

    public function test_owner_cannot_dispatch_more_than_available_main_warehouse_stock(): void
    {
        $stockRequest = StockRequest::create([
            'shop_id'      => $this->shop->id,
            'requested_by' => $this->shopAdmin->id,
            'request_date' => now()->toDateString(),
            'status'       => 'pending',
        ]);

        $reqA = StockRequestItem::create(['request_id' => $stockRequest->id, 'item_id' => $this->itemA->id, 'quantity' => 25]);

        $response = $this->actingAs($this->owner)
            ->post(route('stock-requests.approve', $stockRequest), [
                'selective_dispatch' => 1,
                'selected_items'     => [$reqA->id],
                'quantities'         => [$reqA->id => 25],
            ]);

        $response->assertSessionHas('error');
        $stockRequest->refresh();
        $this->assertEquals('pending', $stockRequest->status);
        $this->assertEquals(20, (int) MainStock::where('item_id', $this->itemA->id)->sum('remaining_quantity'));
        $this->assertEquals(0, StockTransfer::where('request_id', $stockRequest->id)->count());
    }
}
