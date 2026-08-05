<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
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
            'shop_name' => 'Legacy Shop',
            'location'  => 'Mbezi Beach',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
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
            'specification' => '16GB RAM',
            'brand'         => 'HP',
            'model'         => 'G8',
        ]);

        // Add normal stock
        $this->normalStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1500,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);
    }

    public function test_shop_admin_can_add_admin_stock()
    {
        // Create a seller for this shop to notify
        $seller = User::create([
            'name'     => 'Shop Seller',
            'email'    => 'seller@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-admin-stock'), [
            'item_id'       => $this->item->id,
            'quantity'      => 5,
            'buying_price'  => 800,
            'selling_price' => 1200,
            'date_received' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shop_stocks', [
            'shop_id'         => $this->shop->id,
            'item_id'         => $this->item->id,
            'is_admin_stock'  => true,
            'quantity'        => 5,
            'low_stock_alert' => 1,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'item_id'        => $this->item->id,
            'is_admin_stock' => true,
            'notes'          => 'Admin stock added directly to shop',
        ]);

        // Assert notification was created for the seller
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->id,
            'title'   => 'New Admin Stock Added',
            'message' => "Admin has added new stock for \"HP Laptop\" (Qty: 5) to the shop stock.",
        ]);
    }

    public function test_owner_cannot_see_admin_stock_in_inventory()
    {
        // Add admin stock
        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 800,
            'selling_price'      => 1200,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        // Owner index
        $this->actingAs($this->owner);
        $response = $this->get(route('shop-stock.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Admin Stock');
        $response->assertDontSee('TZS 1,200');

        // Owner shop show
        $response = $this->get(route('shops.show', $this->shop));
        $response->assertStatus(200);
        $response->assertDontSee('TZS 1,200');
        $response->assertSee('0'); // units in stock
    }

    public function test_seller_can_sell_admin_stock()
    {
        // Add admin stock
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 800,
            'selling_price'      => 1200,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'customer_name'  => 'Walk-in',
            'sale_status'    => 'completed',
            'items'          => [
                [
                    'shop_stock_id' => $adminStock->id,
                    'quantity'      => 2,
                    'price'         => 1200,
                ]
            ]
        ]);

        $response->assertRedirect();

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        $this->assertTrue($sale->is_admin_stock);

        $log = StockLog::where('transaction_type', 'SALE')->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->is_admin_stock);
    }

    public function test_can_mix_normal_and_admin_stock_in_sale()
    {
        // Add admin stock
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 800,
            'selling_price'      => 1200,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'customer_name'  => 'Walk-in',
            'sale_status'    => 'completed',
            'items'          => [
                [
                    'shop_stock_id' => $this->normalStock->id,
                    'quantity'      => 1,
                    'price'         => 1500,
                ],
                [
                    'shop_stock_id' => $adminStock->id,
                    'quantity'      => 1,
                    'price'         => 1200,
                ]
            ]
        ]);

        $response->assertRedirect();

        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        $this->assertFalse($sale->is_admin_stock); // mixed sale is overall normal

        $items = SaleItem::where('sale_id', $sale->id)->get();
        $this->assertCount(2, $items);

        $normalItem = $items->where('item_id', $this->item->id)->first();
        $adminItem = $items->where('item_id', $this->item->id)->last();

        $logs = StockLog::where('notes', "Sale #{$sale->id}")->get();
        $this->assertCount(2, $logs);
    }

    public function test_owner_reports_exclude_admin_sales()
    {
        // Add admin stock
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 800,
            'selling_price'      => 1200,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        // Create admin stock sale
        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 1200,
            'status'         => 'completed',
            'is_admin_stock' => true,
        ]);

        SaleItem::create([
            'sale_id'           => $sale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 1200,
            'owner_cost_price'  => 800,
            'owner_realized_sp' => 1200,
            'shop_cost_price'   => 800,
            'shop_realized_sp'  => 1200,
        ]);

        // Access reports as owner
        $this->actingAs($this->owner);
        $response = $this->get(route('reports.sales'));
        $response->assertStatus(200);
        $response->assertDontSee('Walk-in');
        $response->assertSee('TZS 0'); // since no normal sales exist
    }

    public function test_shop_admin_can_create_custom_product_and_add_stock()
    {
        $this->actingAs($this->admin);

        $category = Category::first();

        $response = $this->post(route('shop-stock.store-admin-stock'), [
            'create_new_product' => '1',
            'new_item_name'      => 'Admin Custom Keyboard',
            'category_id'        => $category->id,
            'brand'              => 'Logitech',
            'model'              => 'K120',
            'specification'      => 'USB black keyboard',
            'quantity'           => 10,
            'buying_price'       => 500,
            'selling_price'      => 700,
            'date_received'      => now()->toDateString(),
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        // Verify product was created
        $this->assertDatabaseHas('items', [
            'item_name'     => 'Admin Custom Keyboard',
            'is_admin_item' => true,
            'shop_id'       => $this->shop->id,
        ]);

        $item = Item::where('item_name', 'Admin Custom Keyboard')->first();
        $this->assertNotNull($item);

        // Verify stock was created
        $this->assertDatabaseHas('shop_stocks', [
            'shop_id'        => $this->shop->id,
            'item_id'        => $item->id,
            'is_admin_stock' => true,
            'quantity'       => 10,
        ]);

        // Owner check
        $this->actingAs($this->owner);
        $response = $this->get(route('items.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Admin Custom Keyboard');
    }

    public function test_shop_admin_can_create_custom_category_on_the_fly()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.store-admin-stock'), [
            'create_new_product'  => '1',
            'create_new_category' => '1',
            'new_item_name'       => 'Super Gaming Mouse',
            'new_category_name'   => 'Gaming Mice',
            'brand'               => 'Razer',
            'model'               => 'DeathAdder',
            'specification'       => 'Ergonomic gaming mouse',
            'quantity'            => 5,
            'buying_price'        => 300,
            'selling_price'       => 450,
            'date_received'       => now()->toDateString(),
        ]);

        $response->assertRedirect(route('shop-stock.index'));
        $response->assertSessionHas('success');

        // Verify category was created
        $this->assertDatabaseHas('categories', [
            'category_name'     => 'Gaming Mice',
            'is_admin_category' => true,
            'shop_id'           => $this->shop->id,
        ]);

        $category = Category::where('category_name', 'Gaming Mice')->first();
        $this->assertNotNull($category);

        // Verify product was created referencing this category
        $this->assertDatabaseHas('items', [
            'item_name'     => 'Super Gaming Mouse',
            'category_id'   => $category->id,
            'is_admin_item' => true,
        ]);

        // Owner check
        $this->actingAs($this->owner);
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Gaming Mice');
    }

    public function test_shop_admin_can_update_shop_invoice_details_and_view_them_on_invoice()
    {
        $this->actingAs($this->admin);

        // Update settings
        $response = $this->post(route('settings.update'), [
            'system_name'          => 'Admin Shop Custom Name',
            'slogan'               => 'Custom Slogan',
            'company_tin'          => 'TIN-ADMIN-777',
            'company_address'      => 'Admin Office Street 1',
            'company_bank_name'    => 'Admin Bank Corp',
            'company_bank_account' => 'ACC-ADMIN-112233',
            'printer_enabled'      => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify shop model updated
        $shop = $this->shop->fresh();
        $this->assertEquals('Admin Shop Custom Name', $shop->shop_name);
        $this->assertEquals('Custom Slogan', $shop->slogan);
        $this->assertEquals('TIN-ADMIN-777', $shop->tin_number);
        $this->assertEquals('Admin Office Street 1', $shop->address);
        $this->assertEquals('Admin Bank Corp', $shop->bank_name);
        $this->assertEquals('ACC-ADMIN-112233', $shop->bank_account);

        // Create completed sale belonging to this shop
        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 1200,
            'status'         => 'completed',
        ]);

        // Get invoice
        $response = $this->get(route('sales.invoice', $sale));
        $response->assertStatus(200);
        $response->assertSee('Admin Shop Custom Name');
        $response->assertSee('TIN-ADMIN-777');
        $response->assertSee('Admin Office Street 1');
        $response->assertSee('Admin Bank Corp');
        $response->assertSee('ACC-ADMIN-112233');
    }

    public function test_dashboard_loads_correctly_for_owner_and_admin()
    {
        $this->actingAs($this->owner);
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('salesChart');

        $this->actingAs($this->admin);
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('salesChart');
    }

    public function test_owner_can_configure_summary_emails_and_send_test_email()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->owner);

        // Try validation fail (own email is mandatory)
        $response = $this->from(route('settings.index'))->post(route('settings.update'), [
            'system_name'          => 'AMSTROOM',
            'slogan'               => 'Test Slogan',
            'printer_enabled'      => '1',
            'store_pricing_mode'   => 'DEPENDENT',
            'summary_emails'       => 'other@example.com',
            'summary_time'         => '22:00',
        ]);
        $response->assertSessionHasErrors('summary_emails');

        // Try validation fail (invalid time format)
        $response = $this->from(route('settings.index'))->post(route('settings.update'), [
            'system_name'          => 'AMSTROOM',
            'slogan'               => 'Test Slogan',
            'printer_enabled'      => '1',
            'store_pricing_mode'   => 'DEPENDENT',
            'summary_emails'       => "owner@example.com",
            'summary_time'         => '25:00', // Invalid time
        ]);
        $response->assertSessionHasErrors('summary_time');

        // Valid update
        $response = $this->from(route('settings.index'))->post(route('settings.update'), [
            'system_name'          => 'AMSTROOM',
            'slogan'               => 'Test Slogan',
            'printer_enabled'      => '1',
            'store_pricing_mode'   => 'DEPENDENT',
            'summary_emails'       => "owner@example.com, other@example.com", // owner email is 'owner@example.com' from setup
            'summary_time'         => '21:30',
        ]);
        $response->assertRedirect();

        $this->assertEquals("owner@example.com, other@example.com", \App\Models\Setting::get('summary_report_emails'));
        $this->assertEquals("21:30", \App\Models\Setting::get('summary_report_time'));

        // Trigger manual test email send
        $response = $this->post(route('settings.send-summary'));
        $response->assertStatus(200);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('owner@example.com') && $mail->hasTo('other@example.com');
        });
    }

    public function test_shop_admin_can_configure_summary_emails_and_send_test_email()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->admin);

        // Try validation fail (own email is mandatory)
        $response = $this->from(route('settings.index'))->post(route('settings.update'), [
            'system_name'          => 'Shop Custom Name',
            'slogan'               => 'Custom Slogan',
            'printer_enabled'      => '1',
            'summary_emails'       => 'other@example.com',
            'summary_time'         => '22:00',
        ]);
        $response->assertSessionHasErrors('summary_emails');

        // Valid update
        $response = $this->from(route('settings.index'))->post(route('settings.update'), [
            'system_name'          => 'Shop Custom Name',
            'slogan'               => 'Custom Slogan',
            'printer_enabled'      => '1',
            'summary_emails'       => "admin@example.com, other@example.com", // admin email is 'admin@example.com' from setup
            'summary_time'         => '23:15',
        ]);
        $response->assertRedirect();

        $this->assertEquals("admin@example.com, other@example.com", $this->shop->fresh()->summary_emails);
        $this->assertEquals("23:15", $this->shop->fresh()->summary_time);

        // Trigger manual test email send
        $response = $this->post(route('settings.send-summary'));
        $response->assertStatus(200);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com') && $mail->hasTo('other@example.com');
        });
    }

    public function test_artisan_command_sends_summary_reports_when_forced()
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Configure Owner Summary Email
        \App\Models\Setting::set('summary_report_emails', 'owner@example.com, partner@example.com');
        \App\Models\Setting::set('summary_report_time', '18:00');

        // Configure Shop Admin Summary Email
        $this->shop->update([
            'summary_emails' => 'admin@example.com, manager@example.com',
            'summary_time'   => '18:00'
        ]);

        // Run the artisan command with --force (current time is not 18:00)
        $this->travelTo(now()->setTime(12, 0, 0));
        \Illuminate\Support\Facades\Artisan::call('amstroom:send-summaries', ['--force' => true]);

        // Assert mail sent to owners
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('owner@example.com') && $mail->hasTo('partner@example.com');
        });

        // Assert mail sent to shop admins
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com') && $mail->hasTo('manager@example.com');
        });
    }

    public function test_artisan_command_respects_time_schedule()
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Configure Owner Summary Email
        \App\Models\Setting::set('summary_report_emails', 'owner@example.com');
        \App\Models\Setting::set('summary_report_time', '22:00');

        // Configure Shop Admin Summary Email
        $this->shop->update([
            'summary_emails' => 'admin@example.com',
            'summary_time'   => '22:00'
        ]);

        // 1. Run at a non-matching time (e.g., 21:59)
        $tz = env('APP_TIMEZONE', 'Africa/Dar_es_Salaam');
        $this->travelTo(now()->timezone($tz)->setTime(21, 59, 0));

        \Illuminate\Support\Facades\Artisan::call('amstroom:send-summaries');

        // Assert NO mail was sent
        \Illuminate\Support\Facades\Mail::assertNothingSent();

        // 2. Run at matching time (22:00)
        $this->travelTo(now()->timezone($tz)->setTime(22, 0, 0));

        \Illuminate\Support\Facades\Artisan::call('amstroom:send-summaries');

        // Assert mails were sent
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('owner@example.com');
        });
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SummaryReportMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }
}
