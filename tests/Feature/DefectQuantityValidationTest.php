<?php

use App\Models\Category;
use App\Models\Defect;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it prevents submitting defect quantity exceeding available shop stock', function () {
    $shop = Shop::create([
        'shop_name' => 'Defect Test Shop',
        'location'  => 'Test Location',
    ]);

    $shopAdmin = User::factory()->create([
        'role'    => 'shop_admin',
        'shop_id' => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Laptops']);
    $item = Item::create([
        'category_id' => $category->id,
        'item_name'   => 'Defect Test Laptop',
    ]);

    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $item->id,
        'quantity'           => 5,
        'buying_price'       => 200000,
        'selling_price'      => 300000,
        'remaining_quantity' => 5,
        'is_sellable'        => true,
    ]);

    // Attempt to submit defect quantity 10 (which exceeds available stock of 5)
    $payload = [
        'item_id'  => $item->id,
        'quantity' => 10,
        'reason'   => 'Screen broken',
    ];

    $response = $this->actingAs($shopAdmin)->post(route('defects.store'), $payload);
    $response->assertSessionHasErrors('quantity');

    expect(Defect::count())->toBe(0);
});

test('it allows submitting defect quantity within available shop stock', function () {
    $shop = Shop::create([
        'shop_name' => 'Defect Test Shop 2',
        'location'  => 'Test Location',
    ]);

    $shopAdmin = User::factory()->create([
        'role'    => 'shop_admin',
        'shop_id' => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Monitors']);
    $item = Item::create([
        'category_id' => $category->id,
        'item_name'   => 'Dell 24 Inch Monitor',
    ]);

    ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $item->id,
        'quantity'           => 5,
        'buying_price'       => 150000,
        'selling_price'      => 200000,
        'remaining_quantity' => 5,
        'is_sellable'        => true,
    ]);

    // Submit valid defect quantity 2 (within available stock of 5)
    $payload = [
        'item_id'  => $item->id,
        'quantity' => 2,
        'reason'   => 'Dead pixels on display',
    ];

    $response = $this->actingAs($shopAdmin)->post(route('defects.store'), $payload);
    $response->assertRedirect(route('defects.index'));

    expect(Defect::count())->toBe(1);
    expect(Defect::first()->quantity)->toBe(2);
});
