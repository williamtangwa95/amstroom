<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Item $hpItem;
    protected Item $dellItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Create Shop
        $this->shop = Shop::create([
            'shop_name' => 'LegacyInftech',
            'location'  => 'Mbezi Beach',
        ]);

        // Create Users
        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        // Create Category & Items
        $category = Category::create(['category_name' => 'Laptops']);
        $this->hpItem = Item::create([
            'item_name'       => 'HP EliteBook 840 G8',
            'category_id'     => $category->id,
            'specification'   => 'Core i7, 16GB, 512GB SSD',
            'brand'           => 'HP',
            'model'           => 'EliteBook 840 G8',
            'warranty_period' => '1 Year',
        ]);

        $this->dellItem = Item::create([
            'item_name'       => 'Dell Latitude 5420',
            'category_id'     => $category->id,
            'specification'   => 'Core i5, 8GB, 256GB SSD',
            'brand'           => 'Dell',
            'model'           => 'Latitude 5420',
            'warranty_period' => '1 Year',
        ]);

        Setting::set('store_pricing_mode', 'INDEPENDENT');

        // Create Sale 1 (HP EliteBook)
        $sale1 = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'HP Customer',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 100000,
        ]);

        SaleItem::create([
            'sale_id'           => $sale1->id,
            'item_id'           => $this->hpItem->id,
            'quantity'          => 2,
            'selling_price'     => 50000,
            'owner_cost_price'  => 20000,
            'owner_realized_sp' => 35000,
            'shop_cost_price'   => 30000,
            'shop_realized_sp'  => 50000,
        ]);

        // Create Sale 2 (Dell Latitude)
        $sale2 = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Dell Customer',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 80000,
        ]);

        SaleItem::create([
            'sale_id'           => $sale2->id,
            'item_id'           => $this->dellItem->id,
            'quantity'          => 1,
            'selling_price'     => 80000,
            'owner_cost_price'  => 40000,
            'owner_realized_sp' => 60000,
            'shop_cost_price'   => 50000,
            'shop_realized_sp'  => 80000,
        ]);
    }

    public function test_owner_can_view_overall_sales_report()
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('reports.sales.data'));

        $response->assertStatus(200);
        // Both items should be visible in items column HTML
        $response->assertSee('HP EliteBook 840 G8 (x2)');
        $response->assertSee('Dell Latitude 5420 (x1)');
    }

    public function test_owner_can_filter_sales_report_by_item()
    {
        $this->actingAs($this->owner);

        // Filter by HP EliteBook
        $response = $this->get(route('reports.sales.data', ['item_id' => $this->hpItem->id]));

        $response->assertStatus(200);
        // Should see HP EliteBook but NOT Dell Latitude
        $response->assertSee('HP EliteBook 840 G8 (x2)');
        $response->assertDontSee('Dell Latitude 5420 (x1)');
    }

    public function test_shop_admin_can_view_filtered_sales_report()
    {
        $this->actingAs($this->admin);

        // Filter by Dell Latitude
        $response = $this->get(route('reports.sales.data', ['item_id' => $this->dellItem->id]));

        $response->assertStatus(200);
        // Shop Admin Dell revenue: 1 * 80000 = 80,000
        $response->assertSee('TZS 80,000');
        // Shop Admin Dell cost: 1 * 50000 = 50,000
        // Shop Admin Dell profit: 80,000 - 50,000 = 30,000
        $response->assertSee('TZS 30,000');
        // Should see Dell Latitude but NOT HP EliteBook
        $response->assertSee('Dell Latitude 5420 (x1)');
        $response->assertDontSee('HP EliteBook 840 G8 (x2)');
    }

    public function test_owner_can_view_main_store_stock_report()
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('reports.stock', ['type' => 'main']));

        $response->assertStatus(200);
        $response->assertSee('Main Store Stock');
        $response->assertSee('Shop Stock Distribution');
        $response->assertSee('Main Warehouse Stock Summary');
    }

    public function test_non_owner_cannot_view_main_store_stock_report()
    {
        $this->actingAs($this->admin);

        // Try to access type=main
        $response = $this->get(route('reports.stock', ['type' => 'main']));

        $response->assertStatus(200);
        $response->assertDontSee('Main Store Stock');
        $response->assertDontSee('Main Warehouse Stock Summary');
        $response->assertSee('Shop Stocks Inventory');
    }

    public function test_owner_can_send_filtered_sales_report_to_email()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->owner);

        $response = $this->post(route('reports.sales.send-email'), [
            'emails' => 'boss@example.com, partner@example.com',
            'period' => 'monthly',
            'note'   => 'Monthly performance summary.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\FilteredSalesReportMail::class, function ($mail) {
            return $mail->hasTo('boss@example.com')
                && $mail->hasTo('partner@example.com')
                && $mail->reportData['note'] === 'Monthly performance summary.'
                // Owner revenue: HP (2 * 35k = 70k) + Dell (1 * 60k = 60k) = 130,000
                && (float)$mail->reportData['total_revenue'] === 130000.0
                // Owner cost: HP (2 * 20k = 40k) + Dell (1 * 40k = 40k) = 80,000
                // Owner profit: 130,000 - 80,000 = 50,000
                && (float)$mail->reportData['total_profit'] === 50000.0;
        });
    }

    public function test_shop_admin_can_send_filtered_sales_report_to_email()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->admin);

        $response = $this->post(route('reports.sales.send-email'), [
            'emails'  => 'admin@example.com',
            'item_id' => $this->hpItem->id,
            'period'  => 'monthly',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\FilteredSalesReportMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com')
                // Shop admin revenue for HP: 2 * 50k = 100k
                && (float)$mail->reportData['total_revenue'] === 100000.0
                // Shop admin profit for HP: 100k - (2 * 30k) = 40k
                && (float)$mail->reportData['total_profit'] === 40000.0;
        });
    }

    public function test_send_sales_report_validates_email_input()
    {
        $this->actingAs($this->owner);

        $response = $this->postJson(route('reports.sales.send-email'), [
            'emails' => 'invalid-email-string',
        ]);

        $response->assertStatus(422);
    }
}
