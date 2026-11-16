<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainStockSwapTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Category $category;
    protected Item $sourceItem;
    protected Item $targetItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'City Branch',
            'location'  => 'City Center',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $this->category = Category::create([
            'category_name' => 'Monitors',
            'description'   => 'Display screens',
        ]);

        $this->sourceItem = Item::create([
            'item_name'   => 'Prodisplay p201',
            'category_id' => $this->category->id,
            'brand'       => 'HP',
            'model'       => 'P201',
        ]);

        $this->targetItem = Item::create([
            'item_name'   => 'Hp Monitor display 20 inch',
            'category_id' => $this->category->id,
            'brand'       => 'HP',
            'model'       => '20 Inch',
        ]);
    }

    public function test_owner_can_see_swap_button_in_data_table(): void
    {
        $mainStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 100000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        // Owner sees swap button
        $response = $this->actingAs($this->owner)->get(route('main-stock.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('btn-swap-product', $data[0]['actions']);

        // Admin cannot access main-stock data table (owner only)
        $response = $this->actingAs($this->admin)->get(route('main-stock.data'));
        $response->assertStatus(403);
    }

    public function test_owner_can_swap_product_when_target_has_no_prior_stock(): void
    {
        $mainStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 120000,
            'selling_price'      => 160000,
            'stocked_quantity'   => 15,
            'remaining_quantity' => 15,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('main-stock.swap', $mainStock->id), [
            'target_item_id' => $this->targetItem->id,
            'affect_shops'   => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Check main stock updated to target item
        $mainStock->refresh();
        $this->assertEquals($this->targetItem->id, $mainStock->item_id);

        // Check stock log created
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->targetItem->id,
            'transaction_type' => 'ADJUSTMENT',
            'performed_by'     => $this->owner->id,
        ]);
    }

    public function test_owner_can_swap_and_merge_into_existing_target_stock(): void
    {
        $sourceStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 100000,
            'selling_price'      => 140000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 6,
            'date_received'      => now()->toDateString(),
        ]);

        $targetStock = MainStock::create([
            'item_id'            => $this->targetItem->id,
            'buying_price'       => 110000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 14,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('main-stock.swap', $sourceStock->id), [
            'target_item_id' => $this->targetItem->id,
            'affect_shops'   => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Source stock row deleted (consolidated)
        $this->assertDatabaseMissing('main_stocks', ['id' => $sourceStock->id]);

        // Target stock quantities consolidated
        $targetStock->refresh();
        $this->assertEquals(20, $targetStock->remaining_quantity); // 14 + 6
        $this->assertEquals(30, $targetStock->stocked_quantity);   // 20 + 10
        $this->assertEquals(140000, (float)$targetStock->selling_price); // min(150000, 140000)
    }

    public function test_swap_propagates_to_shop_stocks_and_pending_transfers(): void
    {
        $mainStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 100000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        // Existing shop stock of source product
        $sourceShopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->sourceItem->id,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_admin_stock'     => false,
        ]);

        // Existing shop stock of target product
        $targetShopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->targetItem->id,
            'quantity'           => 10,
            'remaining_quantity' => 8,
            'is_admin_stock'     => false,
        ]);

        // Pending transfer
        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now()->toDateString(),
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'item_id'     => $this->sourceItem->id,
            'quantity'    => 2,
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('main-stock.swap', $mainStock->id), [
            'target_item_id' => $this->targetItem->id,
            'affect_shops'   => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Old shop stock row consolidated into target shop stock
        $this->assertDatabaseMissing('shop_stocks', ['id' => $sourceShopStock->id]);

        $targetShopStock->refresh();
        $this->assertEquals(15, $targetShopStock->quantity);           // 10 + 5
        $this->assertEquals(13, $targetShopStock->remaining_quantity); // 8 + 5

        // Pending transfer item updated to target item
        $transferItem->refresh();
        $this->assertEquals($this->targetItem->id, $transferItem->item_id);
    }

    public function test_non_owner_cannot_swap_product(): void
    {
        $mainStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 100000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('main-stock.swap', $mainStock->id), [
            'target_item_id' => $this->targetItem->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_swap_to_same_product(): void
    {
        $mainStock = MainStock::create([
            'item_id'            => $this->sourceItem->id,
            'buying_price'       => 100000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('main-stock.swap', $mainStock->id), [
            'target_item_id' => $this->sourceItem->id,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);
    }
}
