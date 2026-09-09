<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\HandoverReport;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('it prevents duplicate sales when resubmitting with the same idempotency key', function () {
    $shop = Shop::create([
        'shop_name' => 'Idempotency Test Shop',
        'location'  => 'Test Location',
    ]);

    $shopAdmin = User::factory()->create([
        'role'    => 'shop_admin',
        'shop_id' => $shop->id,
    ]);

    $category = Category::create(['category_name' => 'Laptops']);
    $item = Item::create([
        'category_id' => $category->id,
        'item_name'   => 'Test Idempotency Laptop',
        'brand'       => 'TestBrand',
    ]);

    $shopStock = ShopStock::create([
        'shop_id'            => $shop->id,
        'item_id'            => $item->id,
        'quantity'           => 10,
        'buying_price'       => 200000,
        'selling_price'      => 300000,
        'remaining_quantity' => 10,
        'is_sellable'        => true,
    ]);

    $idempotencyKey = (string) Str::uuid();

    $payload = [
        'idempotency_key' => $idempotencyKey,
        'payment_method'  => 'cash',
        'customer_name'   => 'John Doe',
        'items'           => [
            [
                'shop_stock_id' => $shopStock->id,
                'quantity'      => 1,
                'price'         => 300000,
            ],
        ],
    ];

    // First submit attempt
    $response1 = $this->actingAs($shopAdmin)->post(route('sales.store'), $payload);
    $response1->assertRedirect();

    expect(Sale::count())->toBe(1);
    $firstSale = Sale::first();

    // Immediate retry with the same idempotency key (e.g. network reconnection retry)
    $response2 = $this->actingAs($shopAdmin)->post(route('sales.store'), $payload);
    $response2->assertRedirect(route('sales.receipt', $firstSale->id));

    // Assert no second sale was created
    expect(Sale::count())->toBe(1);
});

test('it prevents duplicate expenses when resubmitting with the same idempotency key', function () {
    $shop = Shop::create([
        'shop_name' => 'Expense Test Shop',
        'location'  => 'Test Location',
    ]);

    $shopAdmin = User::factory()->create([
        'role'    => 'shop_admin',
        'shop_id' => $shop->id,
    ]);

    $category = ExpenseCategory::create(['name' => 'Utilities', 'created_by' => $shopAdmin->id]);
    $idempotencyKey = (string) Str::uuid();

    $payload = [
        'idempotency_key'     => $idempotencyKey,
        'expense_category_id' => $category->id,
        'activity'            => 'Electricity Bill Payment',
        'amount'              => 50000,
        'activity_date'       => now()->toDateString(),
    ];

    // First submit
    $response1 = $this->actingAs($shopAdmin)->post(route('expenses.store'), $payload);
    $response1->assertRedirect(route('expenses.index'));
    expect(Expense::count())->toBe(1);

    // Second submit with same token
    $response2 = $this->actingAs($shopAdmin)->post(route('expenses.store'), $payload);
    $response2->assertRedirect(route('expenses.index'));

    // Assert only 1 expense record exists
    expect(Expense::count())->toBe(1);
});

test('it prevents duplicate handover reports when resubmitting with the same idempotency key', function () {
    $shop = Shop::create([
        'shop_name' => 'Handover Test Shop',
        'location'  => 'Test Location',
    ]);

    $shopAdmin = User::factory()->create([
        'role'    => 'shop_admin',
        'shop_id' => $shop->id,
    ]);

    $idempotencyKey = (string) Str::uuid();

    $payload = [
        'idempotency_key'   => $idempotencyKey,
        'shop_id'           => $shop->id,
        'start_date'        => now()->subDay()->toDateString(),
        'end_date'          => now()->toDateString(),
        'actual_amount'     => 100000,
        'commission_amount' => 0,
        'submit_action'     => 'submit',
    ];

    // First submit
    $response1 = $this->actingAs($shopAdmin)->post(route('handovers.store'), $payload);
    $response1->assertRedirect();
    expect(HandoverReport::count())->toBe(1);
    $handover = HandoverReport::first();

    // Second submit with same token
    $response2 = $this->actingAs($shopAdmin)->post(route('handovers.store'), $payload);
    $response2->assertRedirect(route('handovers.show', $handover->id));

    // Assert only 1 handover record exists
    expect(HandoverReport::count())->toBe(1);
});
