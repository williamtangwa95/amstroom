<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockRequest;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReorderSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $shopAdmin;
    protected Shop $shop;
    protected Unit $unit;
    protected Category $category;
    protected Item $outOfStockItem;
    protected Item $fastMovingItem;
    protected Item $healthyItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Kariakoo Branch',
            'location'  => 'Kariakoo, Dar es Salaam',
            'status'    => 'active',
        ]);

        $this->owner = User::create([
            'name'     => 'Main Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shopAdmin = User::create([
            'name'     => 'Branch Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $this->unit = Unit::firstOrCreate([
            'name' => 'Piece',
        ], [
            'symbol' => 'pc',
        ]);

        $this->category = Category::create([
            'category_name' => 'Computers & Laptops',
        ]);

        // 1. Out of stock item (remaining_quantity = 0)
        $this->outOfStockItem = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell Latitude 3310',
            'brand'       => 'Dell',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->outOfStockItem->id,
            'quantity'           => 10,
            'remaining_quantity' => 0,
            'low_stock_alert'    => 5,
            'buying_price'       => 400000,
            'selling_price'      => 550000,
        ]);

        // Main warehouse has 25 available
        MainStock::create([
            'item_id'            => $this->outOfStockItem->id,
            'stocked_quantity'   => 25,
            'remaining_quantity' => 25,
            'buying_price'       => 400000,
            'selling_price'      => 550000,
            'date_received'      => now()->toDateString(),
        ]);

        // 2. Fast moving item (stock low, 1 pcs remaining, sells 1/day)
        $this->fastMovingItem = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'CAT 6 UTP Cable',
            'brand'       => 'D-Link',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->fastMovingItem->id,
            'quantity'           => 50,
            'remaining_quantity' => 1,
            'low_stock_alert'    => 5,
            'buying_price'       => 150000,
            'selling_price'      => 220000,
        ]);

        MainStock::create([
            'item_id'            => $this->fastMovingItem->id,
            'stocked_quantity'   => 100,
            'remaining_quantity' => 100,
            'buying_price'       => 150000,
            'selling_price'      => 220000,
            'date_received'      => now()->toDateString(),
        ]);

        // Record 30 units sold in last 30 days
        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->shopAdmin->id,
            'total_amount'   => 220000 * 30,
            'payment_method' => 'cash',
            'sale_date'      => now()->subDays(5)->toDateString(),
            'status'         => 'completed',
        ]);

        SaleItem::create([
            'sale_id'       => $sale->id,
            'item_id'       => $this->fastMovingItem->id,
            'quantity'      => 30,
            'selling_price' => 220000,
        ]);

        // 3. Healthy item (stock = 100, no risk)
        $this->healthyItem = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Wireless Mouse',
            'brand'       => 'Logitech',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->healthyItem->id,
            'quantity'           => 100,
            'remaining_quantity' => 100,
            'low_stock_alert'    => 5,
            'buying_price'       => 20000,
            'selling_price'      => 35000,
        ]);
    }

    public function test_suggestion_service_detects_finished_and_fast_moving_items()
    {
        $service = app(ReorderSuggestionService::class);
        $suggestions = $service->getSuggestions($this->shop->id, 30, 14);

        // Should suggest outOfStockItem and fastMovingItem, but not healthyItem
        $itemIds = $suggestions->pluck('item_id')->toArray();
        $this->assertContains($this->outOfStockItem->id, $itemIds);
        $this->assertContains($this->fastMovingItem->id, $itemIds);
        $this->assertNotContains($this->healthyItem->id, $itemIds);

        // Verify out of stock item properties
        $outItem = $suggestions->firstWhere('item_id', $this->outOfStockItem->id);
        $this->assertEquals('out_of_stock', $outItem->urgency);
        $this->assertEquals(0, $outItem->current_stock);
        $this->assertEquals(25, $outItem->main_store_available);
        $this->assertGreaterThanOrEqual(1, $outItem->suggested_reorder_qty);

        // Verify fast mover properties
        $fastItem = $suggestions->firstWhere('item_id', $this->fastMovingItem->id);
        $this->assertEquals(1, $fastItem->current_stock);
        $this->assertEquals(30, $fastItem->total_sold);
        $this->assertEquals(1.0, $fastItem->daily_sales_rate);
        $this->assertEquals(1.0, $fastItem->days_remaining);
    }

    public function test_shop_admin_can_view_reorder_suggestions_page()
    {
        $response = $this->actingAs($this->shopAdmin)
            ->get(route('reorder.index'));

        $response->assertStatus(200);
        $response->assertSee('Stock Reorder');
        $response->assertSee('Dell Latitude 3310');
        $response->assertSee('CAT 6 UTP Cable');
        $response->assertSee('Submit Stock Order to Main Store');
    }

    public function test_owner_can_view_reorder_page_with_shop_selector()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('reorder.index', ['shop_id' => $this->shop->id]));

        $response->assertStatus(200);
        $response->assertSee('Direct Transfer to Kariakoo Branch');
    }

    public function test_shop_admin_can_submit_consolidated_stock_request()
    {
        $payload = [
            'shop_id' => $this->shop->id,
            'notes'   => 'Urgent weekend replenishment',
            'items'   => [
                [
                    'item_id'  => $this->outOfStockItem->id,
                    'quantity' => 10,
                ],
                [
                    'item_id'  => $this->fastMovingItem->id,
                    'quantity' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->shopAdmin)
            ->post(route('reorder.request'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_requests', [
            'shop_id'      => $this->shop->id,
            'requested_by' => $this->shopAdmin->id,
            'status'       => 'pending',
            'notes'        => 'Urgent weekend replenishment',
        ]);

        $this->assertDatabaseHas('stock_request_items', [
            'item_id'  => $this->outOfStockItem->id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('stock_request_items', [
            'item_id'  => $this->fastMovingItem->id,
            'quantity' => 15,
        ]);
    }

    public function test_owner_can_execute_direct_transfer_based_on_suggestions()
    {
        $payload = [
            'shop_id' => $this->shop->id,
            'items'   => [
                [
                    'item_id'  => $this->outOfStockItem->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('reorder.transfer'), $payload);

        $response->assertRedirect();

        // Main stock should be deducted from 25 to 15
        $this->assertEquals(15, MainStock::where('item_id', $this->outOfStockItem->id)->sum('remaining_quantity'));

        // Stock transfer should be created in pending_receipt status
        $transfer = StockTransfer::where('to_shop', $this->shop->id)->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('pending_receipt', $transfer->status);

        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $transfer->id,
            'item_id'     => $this->outOfStockItem->id,
            'quantity'    => 10,
            'status'      => 'pending',
        ]);
    }

    public function test_owner_direct_transfer_fails_if_warehouse_stock_insufficient()
    {
        $payload = [
            'shop_id' => $this->shop->id,
            'items'   => [
                [
                    'item_id'  => $this->outOfStockItem->id,
                    'quantity' => 9999, // Way more than warehouse available 25
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('reorder.transfer'), $payload);

        $response->assertSessionHasErrors('transfer');

        // Main stock remains unchanged
        $this->assertEquals(25, MainStock::where('item_id', $this->outOfStockItem->id)->sum('remaining_quantity'));
    }
}
