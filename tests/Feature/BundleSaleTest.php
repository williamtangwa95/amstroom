<?php

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\StockLog;
use App\Models\ItemComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bundle sale and dynamic stock calculation works', function () {
    // 1. Create Shop
    $shop = Shop::create([
        'shop_name' => 'Test Shop',
        'location'  => 'Test Location',
    ]);

    // 2. Create User
    $owner = User::create([
        'name'     => 'System Owner',
        'email'    => 'owner_test_bundle@amstroom.com',
        'password' => bcrypt('password'),
        'role'     => 'owner',
    ]);

    // 3. Create Category
    $category = Category::create(['category_name' => 'Hardware']);

    // 4. Create Leaf Components
    $cpu = Item::create([
        'item_name'   => 'Test CPU Tower',
        'category_id' => $category->id,
    ]);

    $cable = Item::create([
        'item_name'   => 'Test Power Cable',
        'category_id' => $category->id,
    ]);

    $monitor = Item::create([
        'item_name'   => 'Test Monitor',
        'category_id' => $category->id,
    ]);

    // 5. Create Parent Bundle Item
    $bundle = Item::create([
        'item_name'   => 'Test Desktop Computer Full Set',
        'category_id' => $category->id,
    ]);

    // 6. Link Components to Parent
    ItemComponent::create([
        'parent_item_id'    => $bundle->id,
        'component_item_id' => $cpu->id,
        'quantity'          => 1,
    ]);

    ItemComponent::create([
        'parent_item_id'    => $bundle->id,
        'component_item_id' => $cable->id,
        'quantity'          => 2, // 2 cables needed
    ]);

    ItemComponent::create([
        'parent_item_id'    => $bundle->id,
        'component_item_id' => $monitor->id,
        'quantity'          => 1,
    ]);

    // 7. Add stock for components in shop
    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $cpu->id,
        'quantity'           => 5,
        'remaining_quantity' => 5,
        'buying_price'       => 200000,
        'selling_price'      => 300000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $cable->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 5000,
        'selling_price'      => 10000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Add monitor stock
    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $monitor->id,
        'quantity'           => 4,
        'remaining_quantity' => 4,
        'buying_price'       => 100000,
        'selling_price'      => 150000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Create a ShopStock row for the bundle (representing price and initial state)
    // We expect quantity/remaining_quantity to be overridden by components
    $bundleStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $bundle->id,
        'quantity'           => 0,
        'remaining_quantity' => 0,
        'buying_price'       => 0,
        'selling_price'      => 0,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // 8. Assert dynamic stock calculations
    // CPU: 5/1 = 5
    // Cable: 10/2 = 5
    // Monitor: 4/1 = 4
    // Expected dynamic stock: min(5, 5, 4) = 4
    expect($bundleStock->remaining_quantity)->toBe(4);

    // Assert dynamic price calculations
    // Expected dynamic selling price: (300000*1) + (10000*2) + (150000*1) = 470000
    expect((int)$bundleStock->selling_price)->toBe(470000);
    // Expected dynamic buying price: (200000*1) + (5000*2) + (100000*1) = 310000
    expect((int)$bundleStock->buying_price)->toBe(310000);

    // 9. Deduct stock for a sale of 2 bundles
    $bundle->deductStock($shop->id, 2, $owner->id, 999, false, 'Test Customer');

    // 10. Verify component stocks were decremented
    // CPU remaining: 5 - 2 = 3
    expect((int)ShopStock::where('item_id', $cpu->id)->first()->remaining_quantity)->toBe(3);
    // Cable remaining: 10 - 4 = 6
    expect((int)ShopStock::where('item_id', $cable->id)->first()->remaining_quantity)->toBe(6);
    // Monitor remaining: 4 - 2 = 2
    expect((int)ShopStock::where('item_id', $monitor->id)->first()->remaining_quantity)->toBe(2);

    // Verify bundle dynamic stock is updated:
    // CPU: 3/1 = 3
    // Cable: 6/2 = 3
    // Monitor: 2/1 = 2
    // Expected dynamic stock: min(3, 3, 2) = 2
    $bundleStock->refresh();
    expect($bundleStock->remaining_quantity)->toBe(2);

    // Verify stock logs were created
    $logs = StockLog::where('notes', 'like', '%Sale #999%')->get();
    expect($logs->count())->toBe(3);

    $cpuLog = $logs->where('item_id', $cpu->id)->first();
    expect($cpuLog->quantity)->toBe(2);
    expect($cpuLog->notes)->toContain('Component of Test Desktop Computer Full Set');

    $cableLog = $logs->where('item_id', $cable->id)->first();
    expect($cableLog->quantity)->toBe(4);
    expect($cableLog->notes)->toContain('Component of Test Desktop Computer Full Set');

    $monitorLog = $logs->where('item_id', $monitor->id)->first();
    expect($monitorLog->quantity)->toBe(2);
    expect($monitorLog->notes)->toContain('Component of Test Desktop Computer Full Set');
});

test('custom components at point of sale flow works', function () {
    $shop = Shop::create([
        'shop_name' => 'POS Shop',
        'location'  => 'POS Location',
    ]);

    $user = User::create([
        'name'      => 'POS Seller',
        'email'     => 'pos_seller@amstroom.com',
        'password'  => bcrypt('password'),
        'role'      => 'seller',
        'shop_id'   => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Tech']);

    $parent = Item::create([
        'item_name'   => 'HP Bundle Set',
        'category_id' => $category->id,
    ]);

    $hdmi = Item::create([
        'item_name'   => 'HDMI Connector',
        'category_id' => $category->id,
    ]);

    $parentStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $parent->id,
        'quantity'           => 1,
        'remaining_quantity' => 1,
        'buying_price'       => 500000,
        'selling_price'      => 600000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $hdmiStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $hdmi->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 10000,
        'selling_price'      => 15000,
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // Submit a sale via POST request with custom components
    $response = $this->actingAs($user)->post(route('sales.store'), [
        'customer_name'  => 'Dynamic Client',
        'payment_method' => 'cash',
        'items'          => [
            [
                'shop_stock_id' => $parentStock->id,
                'quantity'      => 1,
                'price'         => 600000,
                'components'    => [
                    [
                        'item_id'  => $hdmi->id,
                        'quantity' => 2,
                    ]
                ]
            ]
        ]
    ]);

    $sale = \App\Models\Sale::first();
    $response->assertRedirect(route('sales.receipt', $sale->id));

    // Assert the sale and sale items were created correctly in DB
    $this->assertDatabaseHas('sales', [
        'customer_name' => 'Dynamic Client',
        'total_amount'  => 600000.00,
    ]);

    $parentSaleItem = \App\Models\SaleItem::where('item_id', $parent->id)->first();
    expect($parentSaleItem)->not->toBeNull();
    expect($parentSaleItem->quantity)->toBe(1);

    // Verify child components in sale items
    $childSaleItem = \App\Models\SaleItem::where('item_id', $hdmi->id)->first();
    expect($childSaleItem)->not->toBeNull();
    expect($childSaleItem->parent_id)->toBe($parentSaleItem->id);
    expect($childSaleItem->quantity)->toBe(2);
    expect((float)$childSaleItem->selling_price)->toBe(0.0);

    // Verify stock deduction
    $hdmiStock->refresh();
    expect($hdmiStock->remaining_quantity)->toBe(8); // 10 - 2
});

test('toggle components visibility setting works', function () {
    $owner = User::create([
        'name'     => 'System Owner',
        'email'    => 'owner_toggle@amstroom.com',
        'password' => bcrypt('password'),
        'role'     => 'owner',
    ]);

    $shop = Shop::create([
        'shop_name' => 'Setting Shop',
        'location'  => 'Setting Location',
    ]);

    $category = \App\Models\Category::create([
        'category_name' => 'Setting Category',
    ]);

    $item = \App\Models\Item::create([
        'category_id' => $category->id,
        'item_name' => 'Test Spec Item',
        'selling_price' => 5000,
    ]);

    $mainStock = \App\Models\MainStock::create([
        'item_id' => $item->id,
        'buying_price' => 2000,
        'selling_price' => 5000,
        'stocked_quantity' => 10,
        'remaining_quantity' => 10,
        'date_received' => today(),
    ]);

    $shopStock = \App\Models\ShopStock::create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'buying_price' => 2000,
        'selling_price' => 5000,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'low_stock_alert' => 2,
        'date_received' => today(),
    ]);

    // 1. Toggle warehouse main stock setting
    $response = $this->actingAs($owner)->post(route('settings.toggle-components'), [
        'main_stock_id' => $mainStock->id,
        'enabled'    => 1,
    ]);

    $response->assertJson(['success' => true, 'enabled' => true]);
    $mainStock->refresh();
    expect($mainStock->allow_components)->toBeTrue();

    // Toggle warehouse off
    $response = $this->actingAs($owner)->post(route('settings.toggle-components'), [
        'main_stock_id' => $mainStock->id,
        'enabled'    => 0,
    ]);

    $response->assertJson(['success' => true, 'enabled' => false]);
    $mainStock->refresh();
    expect($mainStock->allow_components)->toBeFalse();

    // 2. Toggle shop setting
    $response = $this->actingAs($owner)->post(route('settings.toggle-components'), [
        'shop_stock_id' => $shopStock->id,
        'enabled' => 1,
    ]);

    $response->assertJson(['success' => true, 'enabled' => true]);
    $shopStock->refresh();
    expect($shopStock->allow_components)->toBeTrue();

    // 3. Bulk toggle warehouse stock
    $response = $this->actingAs($owner)->post(route('settings.toggle-components'), [
        'main_stock_ids' => [$mainStock->id],
        'enabled'        => 1,
    ]);
    $response->assertJson(['success' => true]);
    $mainStock->refresh();
    expect($mainStock->allow_components)->toBeTrue();

    // 4. Bulk toggle shop stock
    $response = $this->actingAs($owner)->post(route('settings.toggle-components'), [
        'shop_stock_ids' => [$shopStock->id],
        'enabled'        => 0,
    ]);
    $response->assertJson(['success' => true]);
    $shopStock->refresh();
    expect($shopStock->allow_components)->toBeFalse();
});
