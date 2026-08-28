<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceFormulaAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Shop $shop;
    protected Item $item;
    protected ShopStock $normalStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Test Amstroom Shop',
            'location'  => 'Dar es Salaam',
        ]);

        $this->admin = User::create([
            'name'                 => 'Shop Admin',
            'email'                => 'admin@example.com',
            'password'             => bcrypt('password'),
            'role'                 => 'shop_admin',
            'shop_id'              => $this->shop->id,
            'allow_stock_addition' => true,
        ]);

        $category = Category::create(['category_name' => 'Electronics']);
        $this->item = Item::create([
            'item_name'     => 'Smart TV',
            'category_id'   => $category->id,
            'specification' => '55 inch UHD',
            'brand'         => 'LG',
            'model'         => '2026',
        ]);

        $this->normalStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 2000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);
    }

    public function test_store_owner_stock_sets_main_store_selling_price_to_double_buying_price()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-owner-stock'), [
            'item_id'       => $this->item->id,
            'quantity'      => 10,
            'buying_price'  => 5000,
            'selling_price' => 8000, // Shop selling price
            'date_received' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        // MainStock buying_price should be 2500, selling_price should be 5000
        $this->assertDatabaseHas('main_stocks', [
            'item_id'       => $this->item->id,
            'buying_price'  => 2500,
            'selling_price' => 5000,
        ]);

        // StockTransferItem buying_price should be 2500, selling_price should be 5000
        $this->assertDatabaseHas('stock_transfer_items', [
            'item_id'       => $this->item->id,
            'buying_price'  => 2500,
            'selling_price' => 5000,
        ]);

        // ShopStock buying_price should be 5000, selling_price should be 8000
        $this->assertDatabaseHas('shop_stocks', [
            'item_id'        => $this->item->id,
            'buying_price'   => 5000,
            'selling_price'  => 8000,
            'is_admin_stock' => false,
        ]);
    }

    public function test_admin_and_owner_can_sell_item_at_or_above_buying_price()
    {
        // Buying price is 1000, Selling price is 2000.
        // Let's sell as Shop Admin at 1200 (which is less than 2000, but greater than 1000).
        $this->actingAs($this->admin);

        $response = $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $this->normalStock->id,
                    'quantity' => 1,
                    'price' => 1200, // < 2000, but >= 1000
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', [
            'shop_id' => $this->shop->id,
            'status' => 'completed',
        ]);
    }

    public function test_seller_cannot_sell_item_below_dedicated_selling_price()
    {
        $seller = User::create([
            'name'     => 'Shop Seller',
            'email'    => 'seller@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop->id,
        ]);

        $this->actingAs($seller);

        $response = $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $this->normalStock->id,
                    'quantity' => 1,
                    'price' => 1200, // Less than dedicated selling price (2000)
                ]
            ]
        ]);

        $this->assertTrue(session()->has('errors') || $response->status() >= 400 || $response->status() == 500);
    }

    public function test_store_multiple_admin_stocks_successfully()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-admin-stock'), [
            'date_received' => now()->toDateString(),
            'products' => [
                [
                    'create_new_product' => false,
                    'item_id' => $this->item->id,
                    'quantity' => 10,
                    'buying_price' => 1500,
                    'selling_price' => 2500,
                ],
                [
                    'create_new_product' => true,
                    'create_new_category' => false,
                    'new_item_name' => 'Brand New Keyboard',
                    'category_id' => $this->item->category_id,
                    'brand' => 'HP',
                    'model' => 'K300',
                    'quantity' => 5,
                    'buying_price' => 2000,
                    'selling_price' => 3000,
                ]
            ]
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        // Check first item
        $this->assertDatabaseHas('shop_stocks', [
            'item_id' => $this->item->id,
            'buying_price' => 1500,
            'selling_price' => 2500,
            'quantity' => 10,
            'is_admin_stock' => true,
        ]);

        // Check second item
        $newItem = Item::where('item_name', 'Brand New Keyboard')->first();
        $this->assertNotNull($newItem);
        $this->assertDatabaseHas('shop_stocks', [
            'item_id' => $newItem->id,
            'buying_price' => 2000,
            'selling_price' => 3000,
            'quantity' => 5,
            'is_admin_stock' => true,
        ]);
    }

    public function test_store_multiple_owner_stocks_successfully()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-owner-stock'), [
            'date_received' => now()->toDateString(),
            'products' => [
                [
                    'create_new_product' => false,
                    'item_id' => $this->item->id,
                    'quantity' => 12,
                    'buying_price' => 4000,
                    'selling_price' => 7000,
                ],
                [
                    'create_new_product' => true,
                    'create_new_category' => false,
                    'new_item_name' => 'Owner New Headset',
                    'category_id' => $this->item->category_id,
                    'brand' => 'Sony',
                    'model' => 'WH-1000XM4',
                    'quantity' => 3,
                    'buying_price' => 8000,
                    'selling_price' => 15000,
                ]
            ]
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        // Check first item
        $this->assertDatabaseHas('shop_stocks', [
            'item_id' => $this->item->id,
            'buying_price' => 4000,
            'selling_price' => 7000,
            'quantity' => 12,
            'is_admin_stock' => false,
        ]);
        $this->assertDatabaseHas('main_stocks', [
            'item_id' => $this->item->id,
            'buying_price' => 2000,
            'selling_price' => 4000,
        ]);

        // Check second item
        $newItem = Item::where('item_name', 'Owner New Headset')->first();
        $this->assertNotNull($newItem);
        $this->assertDatabaseHas('shop_stocks', [
            'item_id' => $newItem->id,
            'buying_price' => 8000,
            'selling_price' => 15000,
            'quantity' => 3,
            'is_admin_stock' => false,
        ]);
        $this->assertDatabaseHas('main_stocks', [
            'item_id' => $newItem->id,
            'buying_price' => 4000,
            'selling_price' => 8000,
        ]);
    }
}
