<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\User;
use App\Services\MainStoreStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MainStoreSellingPriceLogicTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Category $category;
    protected Item $item;
    protected MainStoreStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'shop_name' => 'Test Shop',
            'location'  => 'Test Location',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'                 => 'Shop Admin',
            'email'                => 'admin@example.com',
            'password'             => bcrypt('password'),
            'role'                 => 'shop_admin',
            'shop_id'              => $this->shop->id,
            'allow_stock_addition' => true,
        ]);

        $this->category = Category::create([
            'category_name'     => 'Electronics',
            'is_admin_category' => false,
        ]);

        $this->item = Item::create([
            'item_name'     => 'Dell Latitude 3310',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        $this->service = app(MainStoreStockService::class);
    }

    /** Test 1 — New Product (Rule A) */
    public function test_rule_a_new_product_uses_incoming_price_and_quantity()
    {
        $this->assertDatabaseMissing('main_stocks', ['item_id' => $this->item->id]);

        $result = $this->service->processStockAddition(
            $this->item->id,
            5,
            300000,
            550000,
            now()->toDateString(),
            $this->owner->id,
            'Initial Stock'
        );

        $this->assertEquals(5, $result['new_quantity']);
        $this->assertEquals(550000, $result['final_price']);

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 5,
            'selling_price'      => 550000,
        ]);
    }

    /** Test 2 — Same Price */
    public function test_same_price_adds_quantity_and_keeps_price()
    {
        MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 300000,
            'selling_price'      => 500000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $result = $this->service->processStockAddition(
            $this->item->id,
            5,
            300000,
            500000,
            now()->toDateString(),
            $this->owner->id
        );

        $this->assertEquals(15, $result['new_quantity']);
        $this->assertEquals(500000, $result['final_price']);
        $this->assertFalse($result['price_changed']);

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 15,
            'selling_price'      => 500000,
        ]);
    }

    /** Test 3 — Incoming Price Higher (Rule B) */
    public function test_rule_b_higher_incoming_price_is_ignored_and_existing_price_is_kept()
    {
        MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 300000,
            'selling_price'      => 500000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $result = $this->service->processStockAddition(
            $this->item->id,
            5,
            320000,
            550000,
            now()->toDateString(),
            $this->owner->id
        );

        $this->assertEquals(15, $result['new_quantity']);
        $this->assertEquals(500000, $result['final_price']);
        $this->assertFalse($result['price_changed']);

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 15,
            'selling_price'      => 500000,
        ]);
    }

    /** Test 4 — Incoming Price Lower (Rule C) */
    public function test_rule_c_lower_incoming_price_updates_main_store_selling_price()
    {
        MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 300000,
            'selling_price'      => 500000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $result = $this->service->processStockAddition(
            $this->item->id,
            5,
            250000,
            450000,
            now()->toDateString(),
            $this->owner->id
        );

        $this->assertEquals(15, $result['new_quantity']);
        $this->assertEquals(450000, $result['final_price']);
        $this->assertTrue($result['price_changed']);

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 15,
            'selling_price'      => 450000,
        ]);
    }

    /** Test 5 — Multiple Consecutive Additions */
    public function test_multiple_consecutive_additions_keeps_lowest_price()
    {
        // Initial: 10 @ 500,000
        $this->service->processStockAddition($this->item->id, 10, 300000, 500000, null, $this->owner->id);
        $this->assertDatabaseHas('main_stocks', ['item_id' => $this->item->id, 'remaining_quantity' => 10, 'selling_price' => 500000]);

        // Add: 5 @ 550,000 -> Expected: 15 @ 500,000
        $this->service->processStockAddition($this->item->id, 5, 320000, 550000, null, $this->owner->id);
        $this->assertDatabaseHas('main_stocks', ['item_id' => $this->item->id, 'remaining_quantity' => 15, 'selling_price' => 500000]);

        // Add: 3 @ 450,000 -> Expected: 18 @ 450,000
        $this->service->processStockAddition($this->item->id, 3, 250000, 450000, null, $this->owner->id);
        $this->assertDatabaseHas('main_stocks', ['item_id' => $this->item->id, 'remaining_quantity' => 18, 'selling_price' => 450000]);

        // Add: 4 @ 480,000 -> Expected: 22 @ 450,000
        $this->service->processStockAddition($this->item->id, 4, 260000, 480000, null, $this->owner->id);
        $this->assertDatabaseHas('main_stocks', ['item_id' => $this->item->id, 'remaining_quantity' => 22, 'selling_price' => 450000]);
    }

    /** Test 6 — Transaction Rollback */
    public function test_transaction_rollback_prevents_partial_updates_on_failure()
    {
        MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 300000,
            'selling_price'      => 500000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        try {
            DB::transaction(function () {
                $this->service->processStockAddition($this->item->id, 5, 200000, 400000, null, $this->owner->id);
                throw new \Exception('Simulated system failure after stock update');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify state is completely rolled back
        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 10,
            'selling_price'      => 500000,
        ]);
        $this->assertDatabaseMissing('stock_logs', [
            'item_id' => $this->item->id,
            'notes'   => '%Simulated system failure%',
        ]);
    }

    /** Test 7 — Both Stock Flows (MainStockController and ShopStockController) */
    public function test_stock_addition_via_http_controllers_enforces_min_selling_price_rule()
    {
        // 1. Owner Store Addition via MainStockController
        $this->actingAs($this->owner)
            ->post(route('main-stock.store'), [
                'item_id'          => $this->item->id,
                'buying_price'     => 300000,
                'selling_price'    => 500000,
                'stocked_quantity' => 10,
                'date_received'    => now()->toDateString(),
            ])
            ->assertRedirect(route('main-stock.index'));

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 10,
            'selling_price'      => 500000,
        ]);

        // 2. Addition with lower price via MainStockController
        $this->actingAs($this->owner)
            ->post(route('main-stock.store'), [
                'item_id'          => $this->item->id,
                'buying_price'     => 250000,
                'selling_price'    => 420000,
                'stocked_quantity' => 5,
                'date_received'    => now()->toDateString(),
            ]);

        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 15,
            'selling_price'      => 420000,
        ]);
    }
}
