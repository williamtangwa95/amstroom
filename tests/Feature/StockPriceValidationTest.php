<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPriceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Validation Shop',
            'location'  => 'Mbezi',
        ]);

        $this->owner = User::create([
            'name'     => 'Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name'     => 'HP Laptop',
            'category_id'   => $category->id,
            'brand'         => 'HP',
            'model'         => 'G8',
        ]);
    }

    /**
     * 1. Main store add stock validation
     */
    public function test_main_store_add_stock_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->owner);

        $response = $this->post(route('main-stock.store'), [
            'item_id'          => $this->item->id,
            'buying_price'     => 1000,
            'selling_price'    => 800, // less than buying price
            'stocked_quantity' => 10,
            'date_received'    => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    public function test_main_store_add_stock_succeeds_if_selling_price_gte_buying_price()
    {
        $this->actingAs($this->owner);

        $response = $this->post(route('main-stock.store'), [
            'item_id'          => $this->item->id,
            'buying_price'     => 1000,
            'selling_price'    => 1200, // gte buying price
            'stocked_quantity' => 10,
            'date_received'    => now()->toDateString(),
        ]);

        $response->assertRedirect(route('main-stock.index'));
        $this->assertDatabaseHas('main_stocks', [
            'item_id'       => $this->item->id,
            'buying_price'  => 1000,
            'selling_price' => 1200,
        ]);
    }

    /**
     * 2. Main store edit stock validation
     */
    public function test_main_store_edit_stock_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->owner);

        $mainStock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->put(route('main-stock.update', $mainStock), [
            'buying_price'  => 1000,
            'selling_price' => 900, // less than buying price
            'date_received' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    /**
     * 3. Shop stock add admin stock validation
     */
    public function test_shop_stock_add_admin_stock_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-admin-stock'), [
            'item_id'       => $this->item->id,
            'quantity'      => 5,
            'buying_price'  => 1000,
            'selling_price' => 900, // less than buying price
            'date_received' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    /**
     * 4. Shop stock update price validation
     */
    public function test_shop_stock_update_price_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->admin);

        $shopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $response = $this->patch(route('shop-stock.update-price', $shopStock), [
            'selling_price' => 900, // less than buying price (1000)
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    /**
     * 5. Shop stock approve price validation
     */
    public function test_shop_stock_approve_price_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->admin);

        $shopStock = ShopStock::create([
            'shop_id'               => $this->shop->id,
            'item_id'               => $this->item->id,
            'buying_price'          => 1000,
            'selling_price'         => 1200,
            'quantity'              => 10,
            'remaining_quantity'    => 10,
            'date_received'         => now()->toDateString(),
            'is_price_pending'      => true,
            'pending_selling_price' => 900, // pending is less than buying price
        ]);

        $response = $this->post(route('shop-stock.approve-price', $shopStock), [
            'selling_price' => null,
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    /**
     * 6. Stock transfer approve single item validation
     */
    public function test_stock_transfer_approve_single_item_fails_if_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->admin);

        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now()->toDateString(),
            'status'        => 'pending_receipt',
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id'   => $transfer->id,
            'item_id'       => $this->item->id,
            'quantity'      => 5,
            'buying_price'  => 1000,
            'selling_price' => 1500, // buying price for the shop (owner selling price)
            'status'        => 'pending',
        ]);

        $response = $this->post(route('stock-transfers.approve-item', $transferItem), [
            'selling_price' => 1400, // less than buying price (1500)
        ]);

        $response->assertSessionHasErrors(['selling_price']);
    }

    /**
     * 7. Stock transfer approve bulk items validation
     */
    public function test_stock_transfer_approve_bulk_fails_if_any_selling_price_less_than_buying_price()
    {
        $this->actingAs($this->admin);

        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now()->toDateString(),
            'status'        => 'pending_receipt',
        ]);

        $item1 = StockTransferItem::create([
            'transfer_id'   => $transfer->id,
            'item_id'       => $this->item->id,
            'quantity'      => 5,
            'buying_price'  => 1000,
            'selling_price' => 1500,
            'status'        => 'pending',
        ]);

        $response = $this->post(route('stock-transfers.approve-bulk', $transfer), [
            'item_ids' => [$item1->id],
            'selling_prices' => [
                $item1->id => 1400 // less than buying price (1500)
            ]
        ]);

        $response->assertSessionHasErrors(["selling_prices.{$item1->id}"]);
    }
}
