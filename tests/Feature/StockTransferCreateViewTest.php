<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferCreateViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Shop $shop;
    protected Category $category;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Warehouse Owner',
            'email'    => 'owner@transfer-create.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Kariakoo Branch',
            'location'  => 'Msimbazi Street',
        ]);

        $this->category = Category::create([
            'category_name'     => 'Electronics',
            'is_admin_category' => false,
        ]);

        $this->item = Item::create([
            'item_name'     => 'HP EliteBook 840',
            'brand'         => 'HP',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        MainStock::create([
            'item_id'            => $this->item->id,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 20,
            'buying_price'       => 400000,
            'selling_price'      => 600000,
            'date_received'      => now(),
        ]);
    }

    public function test_stock_transfers_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->owner)->get(route('stock-transfers.create'));

        $response->assertStatus(200);
        $response->assertSee('Direct Stock Assignment');
        $response->assertSee('Target Destination Shop');
        $response->assertSee('Kariakoo Branch');
        $response->assertSee('Msimbazi Street');
        $response->assertSee('HP EliteBook 840');
        $response->assertSee('initShopSelect');
        $response->assertSee('initItemSelect');
        $response->assertSee('Dispatch Stock Now');
    }

    public function test_stock_transfers_store_dispatches_successfully(): void
    {
        $response = $this->actingAs($this->owner)->post(route('stock-transfers.store'), [
            'shop_id' => $this->shop->id,
            'items'   => [
                [
                    'id'  => $this->item->id,
                    'qty' => 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'to_shop'    => $this->shop->id,
            'status'     => 'pending_receipt',
            'from_store' => 'Main Warehouse',
        ]);
    }

    public function test_stock_transfers_store_dispatches_to_multiple_shops_with_custom_per_shop_quantities(): void
    {
        $shop2 = Shop::create([
            'shop_name' => 'Posta Branch',
            'location'  => 'Samora Avenue',
        ]);

        $response = $this->actingAs($this->owner)->post(route('stock-transfers.store'), [
            'shop_ids' => [$this->shop->id, $shop2->id],
            'items'    => [
                [
                    'id'        => $this->item->id,
                    'shop_qtys' => [
                        $this->shop->id => 6,
                        $shop2->id      => 4,
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHasNoErrors();

        $t1 = StockTransfer::where('to_shop', $this->shop->id)->first();
        $t2 = StockTransfer::where('to_shop', $shop2->id)->first();

        $this->assertNotNull($t1);
        $this->assertNotNull($t2);
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $t1->id,
            'item_id'     => $this->item->id,
            'quantity'    => 6,
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $t2->id,
            'item_id'     => $this->item->id,
            'quantity'    => 4,
        ]);

        // Warehouse started with 20, dispatched 6 + 4 = 10 -> 10 remaining
        $this->assertEquals(10, (int) MainStock::where('item_id', $this->item->id)->sum('remaining_quantity'));
    }

    public function test_multi_shop_dispatch_rejects_when_combined_quantity_exceeds_warehouse_stock(): void
    {
        $shop2 = Shop::create([
            'shop_name' => 'Mwanza Branch',
            'location'  => 'Kenyatta Road',
        ]);

        // Warehouse has 20; 12 + 12 = 24 exceeds available stock
        $response = $this->actingAs($this->owner)->post(route('stock-transfers.store'), [
            'shop_ids' => [$this->shop->id, $shop2->id],
            'items'    => [
                [
                    'id'        => $this->item->id,
                    'shop_qtys' => [
                        $this->shop->id => 12,
                        $shop2->id      => 12,
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, StockTransfer::count());
        $this->assertEquals(20, (int) MainStock::where('item_id', $this->item->id)->sum('remaining_quantity'));
    }

    public function test_stock_transfers_store_dispatches_different_products_to_different_shops(): void
    {
        $shop2 = Shop::create([
            'shop_name' => 'Arusha Branch',
            'location'  => 'Clock Tower',
        ]);

        $item2 = Item::create([
            'item_name'     => 'Epson L3250 Printer',
            'brand'         => 'Epson',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        MainStock::create([
            'item_id'            => $item2->id,
            'stocked_quantity'   => 15,
            'remaining_quantity' => 15,
            'buying_price'       => 300000,
            'selling_price'      => 450000,
            'date_received'      => now(),
        ]);

        // Shop 1 gets HP EliteBook (qty 3); Shop 2 gets Epson Printer (qty 4) + HP EliteBook (qty 2)
        $response = $this->actingAs($this->owner)->post(route('stock-transfers.store'), [
            'dispatches' => [
                $this->shop->id => [
                    'shop_id' => $this->shop->id,
                    'items'   => [
                        ['id' => $this->item->id, 'qty' => 3],
                    ],
                ],
                $shop2->id => [
                    'shop_id' => $shop2->id,
                    'items'   => [
                        ['id' => $item2->id, 'qty' => 4],
                        ['id' => $this->item->id, 'qty' => 2],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHasNoErrors();

        $t1 = StockTransfer::where('to_shop', $this->shop->id)->first();
        $t2 = StockTransfer::where('to_shop', $shop2->id)->first();

        $this->assertNotNull($t1);
        $this->assertNotNull($t2);

        // Shop 1 only received HP EliteBook (3), NOT Epson Printer
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $t1->id,
            'item_id'     => $this->item->id,
            'quantity'    => 3,
        ]);
        $this->assertDatabaseMissing('stock_transfer_items', [
            'transfer_id' => $t1->id,
            'item_id'     => $item2->id,
        ]);

        // Shop 2 received Epson Printer (4) and HP EliteBook (2)
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $t2->id,
            'item_id'     => $item2->id,
            'quantity'    => 4,
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $t2->id,
            'item_id'     => $this->item->id,
            'quantity'    => 2,
        ]);

        // Warehouse deductions: item1 20 - (3+2) = 15, item2 15 - 4 = 11
        $this->assertEquals(15, (int) MainStock::where('item_id', $this->item->id)->sum('remaining_quantity'));
        $this->assertEquals(11, (int) MainStock::where('item_id', $item2->id)->sum('remaining_quantity'));
    }
}
