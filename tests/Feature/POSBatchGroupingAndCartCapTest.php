<?php

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleBatchAllocation;
use App\Services\StockAllocationService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->shop = Shop::create([
        'shop_name' => 'Morogoro 2H PLAZA',
        'location'  => 'Morogoro',
    ]);

    $this->seller = User::create([
        'name'     => 'William Tangwa',
        'email'    => 'tangwa@amstroom.com',
        'password' => bcrypt('password'),
        'role'     => 'shop_admin',
        'shop_id'  => $this->shop->id,
    ]);

    $this->category = Category::create(['category_name' => 'Accessories']);

    $this->itemA = Item::create([
        'item_name'     => 'TEST A',
        'category_id'   => $this->category->id,
        'specification' => 'Test Spec A',
    ]);
});

test('Test 1 - Compatible identical batches of same product are presented as one grouped card with aggregated stock', function () {
    // Two separate batches of TEST A with identical prices and admin status
    $batch1 = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $batch2 = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $response = $this->actingAs($this->seller)->get(route('sales.create'));
    $response->assertOk();

    $shopStocks = $response->viewData('shopStocks');

    // Filter stocks for itemA
    $itemAStocks = $shopStocks->filter(fn($s) => $s->item_id == $this->itemA->id);

    // Must be grouped into exactly 1 card
    expect($itemAStocks)->toHaveCount(1);
    // Aggregated stock must equal 20
    expect($itemAStocks->first()->remaining_quantity)->toBe(20);
    expect($itemAStocks->first()->selling_price)->toEqual(2000);
});

test('Test 2 - Batches with different selling prices are kept separate', function () {
    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2500, // Different selling price
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $response = $this->actingAs($this->seller)->get(route('sales.create'));
    $shopStocks = $response->viewData('shopStocks');
    $itemAStocks = $shopStocks->filter(fn($s) => $s->item_id == $this->itemA->id);

    // Must remain 2 separate cards
    expect($itemAStocks)->toHaveCount(2);
});

test('Test 3 - Batches with different buying prices are kept separate', function () {
    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1200,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500, // Different buying price
        'selling_price'      => 2000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $response = $this->actingAs($this->seller)->get(route('sales.create'));
    $shopStocks = $response->viewData('shopStocks');
    $itemAStocks = $shopStocks->filter(fn($s) => $s->item_id == $this->itemA->id);

    // Must remain 2 separate cards
    expect($itemAStocks)->toHaveCount(2);
});

test('Test 4 - Batches with different admin stock status are kept separate', function () {
    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => true, // Admin stock
    ]);

    $response = $this->actingAs($this->seller)->get(route('sales.create'));
    $shopStocks = $response->viewData('shopStocks');
    $itemAStocks = $shopStocks->filter(fn($s) => $s->item_id == $this->itemA->id);

    // Must remain 2 separate cards
    expect($itemAStocks)->toHaveCount(2);
});

test('Test 8 & Test 10 - Grouped POS sale checkout accurately allocates across multiple batches in FIFO order', function () {
    $batch1 = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $batch2 = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Cashier buys 18 units from the grouped card (representative batch1 ID)
    $response = $this->actingAs($this->seller)->post(route('sales.store'), [
        'customer_name'  => 'Walk-in Customer',
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'items'          => [
            [
                'shop_stock_id' => $batch1->id,
                'quantity'      => 18,
                'price'         => 2000,
            ]
        ]
    ]);

    $response->assertRedirect();

    // Check batch deductions: Batch 1 (oldest) = 0 remaining, Batch 2 = 2 remaining
    expect($batch1->fresh()->remaining_quantity)->toBe(0);
    expect($batch2->fresh()->remaining_quantity)->toBe(2);

    // Verify allocations
    $sale = Sale::latest()->first();
    $saleItem = $sale->items->first();
    $allocations = SaleBatchAllocation::where('sale_item_id', $saleItem->id)->orderBy('id')->get();

    expect($allocations)->toHaveCount(2);
    expect($allocations[0]->stock_id)->toBe($batch1->id);
    expect($allocations[0]->quantity)->toBe(10);

    expect($allocations[1]->stock_id)->toBe($batch2->id);
    expect($allocations[1]->quantity)->toBe(8);
});

test('Test 9 - Backend stock changed after page load rejects oversale atomically', function () {
    $batch1 = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->itemA->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 1500,
        'selling_price'      => 2000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Initial stock was 10. Cashier's stale page tries to sell 15.
    $response = $this->actingAs($this->seller)->post(route('sales.store'), [
        'customer_name'  => 'Walk-in Customer',
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'items'          => [
            [
                'shop_stock_id' => $batch1->id,
                'quantity'      => 15, // exceeds 10
                'price'         => 2000,
            ]
        ]
    ]);

    // Sale should not be created, stock must remain intact
    expect($batch1->fresh()->remaining_quantity)->toBe(10);
    expect(Sale::count())->toBe(0);
    expect(SaleBatchAllocation::count())->toBe(0);
});
