<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use App\Http\Controllers\SettingController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockOverallCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected $shop;
    protected $owner;
    protected $shopAdmin;
    protected $category;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'shop_name' => 'Main Test Shop',
            'location'  => 'Dodoma',
            'status'    => 'active',
        ]);

        $this->owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shopAdmin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $this->category = Category::create([
            'category_name' => 'Laptops',
        ]);

        $this->item = Item::create([
            'category_id' => $this->category->id,
            'item_name'   => 'Dell Latitude 5420',
        ]);
    }

    public function test_item_with_multiple_batches_where_overall_remaining_is_above_alert_is_not_low_stock()
    {
        // Batch 1: remaining = 1 (individually <= alert 2)
        $batch1 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'quantity'           => 10,
            'remaining_quantity' => 1,
            'buying_price'       => 1000000,
            'selling_price'      => 1200000,
            'low_stock_alert'    => 2,
            'date_received'      => now()->subDays(10),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Batch 2: remaining = 5
        $batch2 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'quantity'           => 10,
            'remaining_quantity' => 5,
            'buying_price'       => 1050000,
            'selling_price'      => 1250000,
            'low_stock_alert'    => 2,
            'date_received'      => now()->subDays(5),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Batch 3: remaining = 10
        $batch3 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'buying_price'       => 1100000,
            'selling_price'      => 1300000,
            'low_stock_alert'    => 2,
            'date_received'      => now(),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Total remaining = 1 + 5 + 10 = 16 (> 2 alert)
        $this->assertFalse($batch1->isLowStock());
        $this->assertFalse($batch2->isLowStock());
        $this->assertFalse($batch3->isLowStock());

        // Test ShopStockController@index view has lowStockItems = 0
        $response = $this->actingAs($this->shopAdmin)->get(route('shop-stock.index'));
        $response->assertOk();
        $response->assertViewHas('lowStockItems', 0);

        // Test DataTables response does not flag row as low stock
        $dataResponse = $this->actingAs($this->shopAdmin)->getJson(route('shop-stock.data'));
        $dataResponse->assertOk();
        $rows = $dataResponse->json('data');
        foreach ($rows as $row) {
            // Check that remaining column doesn't contain "Low Stock" badge
            $this->assertStringNotContainsString('Low Stock', $row['remaining_qty']);
        }

        // Test DashboardController
        $dashResponse = $this->actingAs($this->shopAdmin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertViewHas('lowStockCount', 0);

        // Test SettingController compileReportData
        $settingCtrl = new SettingController();
        $reportData = $settingCtrl->compileReportData($this->shopAdmin, $this->shop->shop_name);
        $this->assertEquals(0, $reportData['low_stock_alerts']);
    }

    public function test_item_whose_overall_remaining_across_all_batches_is_low_stock_is_detected()
    {
        // Batch 1: remaining = 1
        $batch1 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'quantity'           => 10,
            'remaining_quantity' => 1,
            'buying_price'       => 1000000,
            'selling_price'      => 1200000,
            'low_stock_alert'    => 2,
            'date_received'      => now()->subDays(10),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Batch 2: remaining = 0
        $batch2 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'quantity'           => 10,
            'remaining_quantity' => 0,
            'buying_price'       => 1050000,
            'selling_price'      => 1250000,
            'low_stock_alert'    => 2,
            'date_received'      => now()->subDays(5),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Total remaining = 1 + 0 = 1 (<= 2 alert) -> True!
        $this->assertTrue($batch1->isLowStock());

        // Test ShopStockController@index view has lowStockItems = 1
        $response = $this->actingAs($this->shopAdmin)->get(route('shop-stock.index'));
        $response->assertOk();
        $response->assertViewHas('lowStockItems', 1);

        // Test DashboardController
        $dashResponse = $this->actingAs($this->shopAdmin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertViewHas('lowStockCount', 1);

        // Test SettingController compileReportData
        $settingCtrl = new SettingController();
        $reportData = $settingCtrl->compileReportData($this->shopAdmin, $this->shop->shop_name);
        $this->assertEquals(1, $reportData['low_stock_alerts']);
    }
}
