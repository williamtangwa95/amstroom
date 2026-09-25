<?php

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seller can add component to an existing sale item after sale conducted', function () {
    $shop = Shop::create([
        'shop_name' => 'Shop 1',
        'location'  => 'Dodoma',
    ]);

    $seller = User::create([
        'name'      => 'Seller 1',
        'email'     => 'seller1@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Computers']);

    $parentItem = Item::create([
        'item_name'   => 'HP Desktop Computer',
        'category_id' => $category->id,
    ]);

    $cableItem = Item::create([
        'item_name'   => 'Power Cable',
        'category_id' => $category->id,
    ]);

    // Parent stock has allow_components enabled
    $parentStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $parentItem->id,
        'quantity'           => 5,
        'remaining_quantity' => 4,
        'buying_price'       => 400000,
        'selling_price'      => 500000,
        'allow_components'   => true,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Cable stock
    $cableStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $cableItem->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 5000,
        'selling_price'      => 10000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Create a completed sale
    $sale = Sale::create([
        'shop_id'        => $shop->id,
        'seller_id'      => $seller->id,
        'customer_name'  => 'John Doe',
        'payment_method' => 'cash',
        'total_amount'   => 500000,
        'sale_date'      => today(),
        'status'         => 'completed',
        'is_admin_stock' => false,
    ]);

    $saleItem = SaleItem::create([
        'sale_id'           => $sale->id,
        'item_id'           => $parentItem->id,
        'quantity'          => 1,
        'selling_price'     => 500000,
        'owner_cost_price'  => 400000,
        'owner_realized_sp' => 500000,
        'shop_cost_price'   => 400000,
        'shop_realized_sp'  => 500000,
        'is_admin_stock'    => false,
    ]);

    expect($saleItem->allowsComponents())->toBeTrue();

    // 1. Fetch available components endpoint
    $response = $this->actingAs($seller)->getJson(route('sales.available-components', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['item_name' => 'Power Cable']);

    // 2. Add component to the sale item
    $postResponse = $this->actingAs($seller)->postJson(route('sales.add-component', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]), [
        'component_item_id' => $cableItem->id,
        'quantity'          => 2,
    ]);

    $postResponse->assertOk();
    $postResponse->assertJson(['success' => true]);

    // 3. Verify SaleItem record created for the component
    $childSaleItem = SaleItem::where('sale_id', $sale->id)
        ->where('parent_id', $saleItem->id)
        ->where('item_id', $cableItem->id)
        ->first();

    expect($childSaleItem)->not->toBeNull();
    expect($childSaleItem->quantity)->toBe(2);
    expect((float)$childSaleItem->selling_price)->toBe(0.0);

    // 4. Verify inventory deduction for the component
    $cableStock->refresh();
    expect($cableStock->remaining_quantity)->toBe(8); // 10 - 2

    // 5. Verify StockLog created
    $log = StockLog::where('item_id', $cableItem->id)->where('transaction_type', 'SALE')->first();
    expect($log)->not->toBeNull();
    expect($log->quantity)->toBe(2);
    expect($log->notes)->toContain('Component of HP Desktop Computer');

    // 6. Test removing component restores stock
    $deleteResponse = $this->actingAs($seller)->deleteJson(route('sales.remove-component', [
        'sale'          => $sale->id,
        'componentItem' => $childSaleItem->id,
    ]));

    $deleteResponse->assertOk();
    $deleteResponse->assertJson(['success' => true]);

    $cableStock->refresh();
    expect($cableStock->remaining_quantity)->toBe(10); // restored back to 10
    expect(SaleItem::find($childSaleItem->id))->toBeNull();
});

test('adding component rejects if insufficient stock available', function () {
    $shop = Shop::create([
        'shop_name' => 'Shop 2',
        'location'  => 'Mwanza',
    ]);

    $seller = User::create([
        'name'      => 'Seller 2',
        'email'     => 'seller2@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Computers']);

    $parentItem = Item::create([
        'item_name'   => 'Dell Workstation',
        'category_id' => $category->id,
    ]);

    $mouseItem = Item::create([
        'item_name'   => 'Wireless Mouse',
        'category_id' => $category->id,
    ]);

    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $parentItem->id,
        'quantity'           => 1,
        'remaining_quantity' => 1,
        'buying_price'       => 800000,
        'selling_price'      => 950000,
        'allow_components'   => true,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Mouse stock with only 1 in stock
    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $mouseItem->id,
        'quantity'           => 1,
        'remaining_quantity' => 1,
        'buying_price'       => 15000,
        'selling_price'      => 25000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $shop->id,
        'seller_id'      => $seller->id,
        'customer_name'  => 'Jane Doe',
        'payment_method' => 'cash',
        'total_amount'   => 950000,
        'sale_date'      => today(),
        'status'         => 'completed',
        'is_admin_stock' => false,
    ]);

    $saleItem = SaleItem::create([
        'sale_id'           => $sale->id,
        'item_id'           => $parentItem->id,
        'quantity'          => 1,
        'selling_price'     => 950000,
        'owner_cost_price'  => 800000,
        'owner_realized_sp' => 950000,
        'shop_cost_price'   => 800000,
        'shop_realized_sp'  => 950000,
        'is_admin_stock'    => false,
    ]);

    // Try to add 5 mice when only 1 is available
    $response = $this->actingAs($seller)->postJson(route('sales.add-component', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]), [
        'component_item_id' => $mouseItem->id,
        'quantity'          => 5,
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['success' => false]);
});

test('owner can add component to main store direct sale', function () {
    $owner = User::create([
        'name'      => 'Main Owner',
        'email'     => 'main_owner@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'owner',
    ]);

    $category = Category::create(['category_name' => 'Electronics']);

    $parent = Item::create([
        'item_name'   => 'Server Rack Bundle',
        'category_id' => $category->id,
    ]);

    $switch = Item::create([
        'item_name'   => 'Network Switch 24-Port',
        'category_id' => $category->id,
    ]);

    MainStock::create([
        'item_id'            => $parent->id,
        'buying_price'       => 1000000,
        'selling_price'      => 1500000,
        'stocked_quantity'   => 2,
        'remaining_quantity' => 2,
        'allow_components'   => true,
        'date_received'      => today(),
    ]);

    $switchStock = MainStock::create([
        'item_id'            => $switch->id,
        'buying_price'       => 200000,
        'selling_price'      => 300000,
        'stocked_quantity'   => 5,
        'remaining_quantity' => 5,
        'date_received'      => today(),
    ]);

    $sale = Sale::create([
        'shop_id'        => null,
        'seller_id'      => $owner->id,
        'customer_name'  => 'Enterprise Client',
        'payment_method' => 'bank_transfer',
        'total_amount'   => 1500000,
        'sale_date'      => today(),
        'status'         => 'completed',
        'is_admin_stock' => false,
    ]);

    $saleItem = SaleItem::create([
        'sale_id'           => $sale->id,
        'item_id'           => $parent->id,
        'quantity'          => 1,
        'selling_price'     => 1500000,
        'owner_cost_price'  => 1000000,
        'owner_realized_sp' => 1500000,
        'shop_cost_price'   => 1000000,
        'shop_realized_sp'  => 1500000,
        'is_admin_stock'    => false,
    ]);

    expect($saleItem->allowsComponents())->toBeTrue();

    $response = $this->actingAs($owner)->postJson(route('sales.add-component', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]), [
        'component_item_id' => $switch->id,
        'quantity'          => 1,
    ]);

    $response->assertOk();
    $switchStock->refresh();
    expect($switchStock->remaining_quantity)->toBe(4); // 5 - 1
});

test('seller from another shop cannot modify components of a sale', function () {
    $shop1 = Shop::create(['shop_name' => 'Shop 1', 'location' => 'Loc 1']);
    $shop2 = Shop::create(['shop_name' => 'Shop 2', 'location' => 'Loc 2']);

    $seller1 = User::create([
        'name'      => 'Seller 1',
        'email'     => 's1@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop1->id,
    ]);

    $seller2 = User::create([
        'name'      => 'Seller 2',
        'email'     => 's2@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop2->id,
    ]);

    $category = Category::create(['category_name' => 'Tech']);
    $item = Item::create(['item_name' => 'Bundle', 'category_id' => $category->id]);
    $comp = Item::create(['item_name' => 'Part', 'category_id' => $category->id]);

    $sale = Sale::create([
        'shop_id'        => $shop1->id,
        'seller_id'      => $seller1->id,
        'customer_name'  => 'Cust',
        'payment_method' => 'cash',
        'total_amount'   => 1000,
        'sale_date'      => today(),
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $item->id,
        'quantity'      => 1,
        'selling_price' => 1000,
    ]);

    // Seller 2 attempts to add component to Shop 1's sale
    $response = $this->actingAs($seller2)->postJson(route('sales.add-component', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]), [
        'component_item_id' => $comp->id,
        'quantity'          => 1,
    ]);

    $response->assertStatus(403);
});

test('component deduction properly deducts from active batch when older depleted batches exist', function () {
    $shop = Shop::create(['shop_name' => 'Shop Batch', 'location' => 'Arusha']);

    $seller = User::create([
        'name'      => 'Seller Batch',
        'email'     => 'batch_seller@test.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Accessories']);
    $parentItem = Item::create(['item_name' => 'Monitor Set', 'category_id' => $category->id]);
    $powerCable = Item::create(['item_name' => 'Power Cable Batch', 'category_id' => $category->id]);

    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $parentItem->id,
        'quantity'           => 1,
        'remaining_quantity' => 1,
        'buying_price'       => 100000,
        'selling_price'      => 120000,
        'allow_components'   => true,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Old depleted batch with 0 remaining
    $depletedBatch = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $powerCable->id,
        'quantity'           => 5,
        'remaining_quantity' => 0,
        'buying_price'       => 5000,
        'selling_price'      => 8000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
        'date_received'      => '2026-08-01',
    ]);

    // Newly added batch with 10 remaining
    $activeBatch = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $powerCable->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 5000,
        'selling_price'      => 8000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
        'date_received'      => '2026-09-25',
    ]);

    $sale = Sale::create([
        'shop_id'        => $shop->id,
        'seller_id'      => $seller->id,
        'customer_name'  => 'Batch Customer',
        'payment_method' => 'cash',
        'total_amount'   => 120000,
        'sale_date'      => today(),
        'status'         => 'completed',
        'is_admin_stock' => false,
    ]);

    $saleItem = SaleItem::create([
        'sale_id'           => $sale->id,
        'item_id'           => $parentItem->id,
        'quantity'          => 1,
        'selling_price'     => 120000,
        'owner_cost_price'  => 100000,
        'owner_realized_sp' => 120000,
        'shop_cost_price'   => 100000,
        'shop_realized_sp'  => 120000,
        'is_admin_stock'    => false,
    ]);

    $response = $this->actingAs($seller)->postJson(route('sales.add-component', [
        'sale'     => $sale->id,
        'saleItem' => $saleItem->id,
    ]), [
        'component_item_id' => $powerCable->id,
        'quantity'          => 2,
    ]);

    $response->assertOk();

    $depletedBatch->refresh();
    $activeBatch->refresh();

    // Depleted batch should stay 0
    expect($depletedBatch->remaining_quantity)->toBe(0);
    // Active batch with available stock should be decremented: 10 - 2 = 8
    expect($activeBatch->remaining_quantity)->toBe(8);
});

