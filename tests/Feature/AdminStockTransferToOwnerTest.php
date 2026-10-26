<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Item;
use App\Models\ShopStock;
use App\Models\StockTransfer;
use App\Models\MainStock;
use App\Models\StockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockTransferToOwnerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $otherAdmin;
    protected User $owner;
    protected Shop $shop;
    protected Shop $otherShop;
    protected Category $category;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Main Town Shop',
            'location'  => 'City Center',
        ]);

        $this->otherShop = Shop::create([
            'shop_name' => 'Branch Shop',
            'location'  => 'Uptown',
        ]);

        $this->owner = User::create([
            'name'     => 'Boss Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin One',
            'email'    => 'admin1@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
            'allow_stock_addition' => true,
        ]);

        $this->otherAdmin = User::create([
            'name'     => 'Shop Admin Two',
            'email'    => 'admin2@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->otherShop->id,
            'allow_stock_addition' => true,
        ]);

        $this->category = Category::create([
            'category_name'     => 'Accessories',
            'is_admin_category' => true,
            'shop_id'           => $this->shop->id,
        ]);

        $this->item = Item::create([
            'item_name'     => 'Gaming Mouse G502',
            'category_id'   => $this->category->id,
            'is_admin_item' => true,
            'shop_id'       => $this->shop->id,
        ]);
    }

    public function test_can_transfer_full_untouched_admin_stock_to_owner_stock()
    {
        $this->actingAs($this->admin);

        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => '2026-10-01',
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $response = $this->post(route('shop-stock.transfer-to-owner', $adminStock), [
            'buying_price'  => 50000,
            'selling_price' => 75000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Existing batch is now Owner Stock in place
        $adminStock->refresh();
        $this->assertFalse((bool) $adminStock->is_admin_stock);
        $this->assertEquals(10, $adminStock->quantity);
        $this->assertEquals(10, $adminStock->remaining_quantity);

        // 2. Item & category converted to master owner catalog
        $this->item->refresh();
        $this->assertFalse((bool) $this->item->is_admin_item);
        $this->assertNull($this->item->shop_id);

        $this->category->refresh();
        $this->assertFalse((bool) $this->category->is_admin_category);
        $this->assertNull($this->category->shop_id);

        // 3. MainStock reference created
        $this->assertDatabaseHas('main_stocks', [
            'item_id' => $this->item->id,
            'selling_price' => 50000, // min buying/selling reference
        ]);

        // 4. StockTransfer created
        $this->assertDatabaseHas('stock_transfers', [
            'to_shop' => $this->shop->id,
            'status'  => 'received',
        ]);

        // 5. StockLog created
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->item->id,
            'is_admin_stock'   => false,
            'transaction_type' => 'STOCK_RECEIVED',
        ]);
    }

    public function test_can_transfer_partially_sold_admin_stock_to_owner_stock()
    {
        $this->actingAs($this->admin);

        // 10 initial, 4 sold, 6 remaining
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 10,
            'remaining_quantity' => 6,
            'date_received'      => '2026-10-01',
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $response = $this->post(route('shop-stock.transfer-to-owner', $adminStock), [
            'buying_price'  => 52000,
            'selling_price' => 80000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Original batch MUST retain the 4 sold units and become remaining = 0 (finished admin stock)
        $adminStock->refresh();
        $this->assertTrue((bool) $adminStock->is_admin_stock, 'Original batch should remain as admin stock for past sales integrity');
        $this->assertEquals(4, $adminStock->quantity, 'Original batch quantity should match sold count');
        $this->assertEquals(0, $adminStock->remaining_quantity, 'Original batch remaining quantity must be 0 (finished)');

        // 2. A new batch MUST be created for the remaining 6 units as Owner Stock
        $newOwnerStock = ShopStock::where('shop_id', $this->shop->id)
            ->where('item_id', $this->item->id)
            ->where('is_admin_stock', false)
            ->first();

        $this->assertNotNull($newOwnerStock, 'A new Owner Stock batch must be created');
        $this->assertEquals(6, $newOwnerStock->quantity);
        $this->assertEquals(6, $newOwnerStock->remaining_quantity);
        $this->assertEquals(52000, (float) $newOwnerStock->buying_price);
        $this->assertEquals(80000, (float) $newOwnerStock->selling_price);

        // 3. Stock Logs for both batches
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->item->id,
            'is_admin_stock'   => true,
            'transaction_type' => 'ADJUSTMENT',
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->item->id,
            'is_admin_stock'   => false,
            'transaction_type' => 'STOCK_RECEIVED',
        ]);
    }

    public function test_cannot_transfer_completely_sold_out_stock()
    {
        $this->actingAs($this->admin);

        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 5,
            'remaining_quantity' => 0, // completely sold out
            'is_admin_stock'     => true,
        ]);

        $response = $this->post(route('shop-stock.transfer-to-owner', $adminStock), [
            'buying_price'  => 50000,
            'selling_price' => 75000,
        ]);

        $response->assertSessionHas('error');
        $adminStock->refresh();
        $this->assertTrue((bool) $adminStock->is_admin_stock);
        $this->assertEquals(0, $adminStock->remaining_quantity);
    }

    public function test_unauthorized_shop_admin_cannot_transfer_another_shops_stock()
    {
        $this->actingAs($this->otherAdmin);

        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id, // belongs to Shop 1, but acting as Admin 2
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'is_admin_stock'     => true,
        ]);

        $response = $this->post(route('shop-stock.transfer-to-owner', $adminStock));
        $response->assertStatus(403);
    }

    public function test_selling_price_less_than_buying_price_is_rejected()
    {
        $this->actingAs($this->admin);

        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'is_admin_stock'     => true,
        ]);

        $response = $this->post(route('shop-stock.transfer-to-owner', $adminStock), [
            'buying_price'  => 60000,
            'selling_price' => 40000, // less than buying price!
        ]);

        $response->assertSessionHas('error');
        $adminStock->refresh();
        $this->assertTrue((bool) $adminStock->is_admin_stock);
    }

    public function test_ajax_request_returns_json_response()
    {
        $this->actingAs($this->admin);

        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 50000,
            'selling_price'      => 75000,
            'quantity'           => 10,
            'remaining_quantity' => 7,
            'is_admin_stock'     => true,
        ]);

        $response = $this->postJson(route('shop-stock.transfer-to-owner', $adminStock), [
            'buying_price'  => 50000,
            'selling_price' => 75000,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('7 unit(s)', $response->json('message'));
        $this->assertStringContainsString('3 sold unit(s)', $response->json('message'));
    }
}
