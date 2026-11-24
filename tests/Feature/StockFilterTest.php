<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $shopAdmin;
    protected Shop $shop;
    protected Category $categoryLaptops;
    protected Category $categoryPhones;
    protected Item $laptopItem;
    protected Item $phoneItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Main City Shop',
            'location'  => 'Center',
        ]);

        $this->owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@filter-test.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shopAdmin = User::create([
            'name'     => 'Shop Admin User',
            'email'    => 'admin@filter-test.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $this->categoryLaptops = Category::create([
            'category_name'     => 'Laptops',
            'is_admin_category' => false,
        ]);

        $this->categoryPhones = Category::create([
            'category_name'     => 'Phones',
            'is_admin_category' => false,
        ]);

        $this->laptopItem = Item::create([
            'item_name'     => 'ThinkPad T480',
            'category_id'   => $this->categoryLaptops->id,
            'is_admin_item' => false,
        ]);

        $this->phoneItem = Item::create([
            'item_name'     => 'iPhone 13',
            'category_id'   => $this->categoryPhones->id,
            'is_admin_item' => false,
        ]);

        // Main store stock batches
        MainStock::create([
            'item_id'            => $this->laptopItem->id,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'buying_price'       => 500000,
            'selling_price'      => 700000,
            'date_received'      => now(),
        ]);

        MainStock::create([
            'item_id'            => $this->phoneItem->id,
            'stocked_quantity'   => 5,
            'remaining_quantity' => 5,
            'buying_price'       => 900000,
            'selling_price'      => 1200000,
            'date_received'      => now(),
        ]);

        // Shop stock batches
        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->laptopItem->id,
            'quantity'           => 6,
            'remaining_quantity' => 6,
            'buying_price'       => 500000,
            'selling_price'      => 750000,
            'low_stock_alert'    => 2,
            'is_admin_stock'     => false,
            'date_received'      => now(),
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->phoneItem->id,
            'quantity'           => 3,
            'remaining_quantity' => 3,
            'buying_price'       => 900000,
            'selling_price'      => 1250000,
            'low_stock_alert'    => 1,
            'is_admin_stock'     => false,
            'date_received'      => now(),
        ]);
    }

    public function test_main_stock_index_loads_categories_and_items(): void
    {
        $response = $this->actingAs($this->owner)->get(route('main-stock.index'));
        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('items');
        $response->assertSee('Filter by Category');
        $response->assertSee('Filter by Product');
    }

    public function test_main_stock_data_can_filter_by_category(): void
    {
        // Filter by Laptops category
        $response = $this->actingAs($this->owner)->getJson(route('main-stock.data', [
            'category_id' => $this->categoryLaptops->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('ThinkPad T480', $data[0]['product']);

        // Filter by Phones category
        $responsePhone = $this->actingAs($this->owner)->getJson(route('main-stock.data', [
            'category_id' => $this->categoryPhones->id,
        ]));
        $responsePhone->assertStatus(200);
        $dataPhone = $responsePhone->json('data');
        $this->assertCount(1, $dataPhone);
        $this->assertStringContainsString('iPhone 13', $dataPhone[0]['product']);
    }

    public function test_main_stock_data_can_filter_by_product(): void
    {
        $response = $this->actingAs($this->owner)->getJson(route('main-stock.data', [
            'item_id' => $this->phoneItem->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('iPhone 13', $data[0]['product']);
    }

    public function test_shop_stock_index_loads_categories_and_items_for_owner_and_shop_admin(): void
    {
        // As Owner
        $ownerResp = $this->actingAs($this->owner)->get(route('shop-stock.index'));
        $ownerResp->assertStatus(200);
        $ownerResp->assertViewHas('categories');
        $ownerResp->assertViewHas('items');
        $ownerResp->assertSee('Filter by Category');
        $ownerResp->assertSee('Filter by Product');

        // As Shop Admin
        $adminResp = $this->actingAs($this->shopAdmin)->get(route('shop-stock.index'));
        $adminResp->assertStatus(200);
        $adminResp->assertViewHas('categories');
        $adminResp->assertViewHas('items');
        $adminResp->assertSee('Filter by Category');
        $adminResp->assertSee('Filter by Product');
    }

    public function test_shop_stock_data_can_filter_by_category(): void
    {
        // Filter by Laptops category
        $response = $this->actingAs($this->owner)->getJson(route('shop-stock.data', [
            'shop_id'     => $this->shop->id,
            'category_id' => $this->categoryLaptops->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('ThinkPad T480', $data[0]['product']);

        // Filter by Phones category
        $responsePhone = $this->actingAs($this->owner)->getJson(route('shop-stock.data', [
            'shop_id'     => $this->shop->id,
            'category_id' => $this->categoryPhones->id,
        ]));
        $responsePhone->assertStatus(200);
        $dataPhone = $responsePhone->json('data');
        $this->assertCount(1, $dataPhone);
        $this->assertStringContainsString('iPhone 13', $dataPhone[0]['product']);
    }

    public function test_shop_stock_data_can_filter_by_product(): void
    {
        $response = $this->actingAs($this->owner)->getJson(route('shop-stock.data', [
            'shop_id' => $this->shop->id,
            'item_id' => $this->laptopItem->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('ThinkPad T480', $data[0]['product']);
    }

    public function test_finished_stocks_index_loads_categories_and_items(): void
    {
        $response = $this->actingAs($this->owner)->get(route('shop-stock.finished', ['shop_id' => $this->shop->id]));

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('items');
        $response->assertSee('Filter by Category');
        $response->assertSee('Filter by Product');
    }

    public function test_finished_stocks_data_can_filter_by_category_and_product(): void
    {
        // Create an item with finished (0) stock
        $finishedLaptop = Item::create([
            'item_name'     => 'Dell XPS 13',
            'category_id'   => $this->categoryLaptops->id,
            'is_admin_item' => false,
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $finishedLaptop->id,
            'quantity'           => 5,
            'remaining_quantity' => 0,
            'buying_price'       => 600000,
            'selling_price'      => 850000,
            'low_stock_alert'    => 1,
            'is_admin_stock'     => false,
            'date_received'      => now(),
        ]);

        $finishedPhone = Item::create([
            'item_name'     => 'Samsung Galaxy S22',
            'category_id'   => $this->categoryPhones->id,
            'is_admin_item' => false,
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $finishedPhone->id,
            'quantity'           => 4,
            'remaining_quantity' => 0,
            'buying_price'       => 700000,
            'selling_price'      => 950000,
            'low_stock_alert'    => 1,
            'is_admin_stock'     => false,
            'date_received'      => now(),
        ]);

        // Filter by Laptops Category
        $responseCat = $this->actingAs($this->owner)->getJson(route('shop-stock.finished-data', [
            'shop_id'     => $this->shop->id,
            'category_id' => $this->categoryLaptops->id,
        ]));
        $responseCat->assertStatus(200);
        $dataCat = $responseCat->json('data');
        $this->assertCount(1, $dataCat);
        $this->assertStringContainsString('Dell XPS 13', $dataCat[0]['product']);

        // Filter by Product
        $responseProd = $this->actingAs($this->owner)->getJson(route('shop-stock.finished-data', [
            'shop_id' => $this->shop->id,
            'item_id' => $finishedPhone->id,
        ]));
        $responseProd->assertStatus(200);
        $dataProd = $responseProd->json('data');
        $this->assertCount(1, $dataProd);
        $this->assertStringContainsString('Samsung Galaxy S22', $dataProd[0]['product']);
    }
}
