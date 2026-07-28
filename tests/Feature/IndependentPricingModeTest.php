<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndependentPricingModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $seller;
    protected Shop $shop;
    protected Item $item;
    protected MainStock $mainStock;

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

        $this->seller = User::create([
            'name'     => 'Shop Seller',
            'email'    => 'seller_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop->id,
        ]);

        // Create Category and Item
        $category = Category::create([
            'category_name' => 'Electronics',
            'description'   => 'Gadgets',
        ]);

        $this->item = Item::create([
            'item_name'   => 'MicroKingdom Keyboard',
            'category_id' => $category->id,
            'brand'       => 'MicroKingdom',
            'model'       => 'MK-100',
        ]);

        // Create Main Stock
        $this->mainStock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 20000,
            'selling_price'      => 45000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);
    }

    public function test_can_toggle_store_pricing_mode_setting()
    {
        $this->actingAs($this->owner);

        // Get settings page
        $response = $this->get(route('settings.index'));
        $response->assertStatus(200);

        // Update settings to INDEPENDENT
        $response = $this->post(route('settings.update'), [
            'system_name'        => 'AMSTROOM',
            'slogan'             => 'Technology Innovations',
            'printer_enabled'    => '1',
            'store_pricing_mode' => 'INDEPENDENT',
        ]);

        $response->assertRedirect();
        $this->assertEquals('INDEPENDENT', Setting::get('store_pricing_mode'));
    }

    public function test_independent_mode_sync_and_lock_workflow()
    {
        Setting::set('store_pricing_mode', 'INDEPENDENT');

        // Create a ShopStock for the item
        $shopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 45000,
            'selling_price'      => 50000,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_sellable'        => true,
            'is_price_pending'   => false,
        ]);

        $this->actingAs($this->owner);

        // Update Main Store Selling Price
        $response = $this->put(route('main-stock.update', $this->mainStock), [
            'buying_price'  => 20000,
            'selling_price' => 48000, // New SP
            'date_received' => now()->toDateString(),
        ]);

        $response->assertRedirect();

        // 1. Auto-Update Sub-Store BP: ShopStock buying_price should be updated to match the new Main Store SP (48000)
        // 2. Lock Item (Pending Approval): is_sellable should be false, is_price_pending should be true
        $shopStock->refresh();
        $this->assertEquals(48000, (float)$shopStock->buying_price);
        $this->assertFalse($shopStock->is_sellable);
        $this->assertTrue($shopStock->is_price_pending);

        // 3. Notification: In-app notification should be sent to Sub-Store Admin & Seller
        $expectedNotificationText = 'Main Store updated transfer price for MicroKingdom Keyboard. Please review and update your Selling Price to restore sales eligibility.';
        
        $adminNotification = Notification::where('user_id', $this->admin->id)->latest()->first();
        $this->assertNotNull($adminNotification);
        $this->assertEquals($expectedNotificationText, $adminNotification->message);

        $sellerNotification = Notification::where('user_id', $this->seller->id)->latest()->first();
        $this->assertNotNull($sellerNotification);
        $this->assertEquals($expectedNotificationText, $sellerNotification->message);

        // 4. Prevent POS/Checkout: POS store/checkout should fail for the locked item
        $this->actingAs($this->seller);

        $response = $this->post(route('sales.store'), [
            'customer_name'  => 'POS Customer',
            'payment_method' => 'cash',
            'items'          => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity'      => 1,
                    'price'         => 50000,
                ]
            ]
        ]);

        $this->assertTrue(session()->has('errors') || $response->status() >= 400 || $response->status() == 500);

        // 5. Approval & Unlock Flow: Admin saves a new selling price
        $this->actingAs($this->admin);

        $response = $this->post(route('shop-stock.approve-price', $shopStock), [
            'selling_price' => 55000,
        ]);

        $response->assertRedirect();

        $shopStock->refresh();
        // Item status is now active (is_sellable = true) and is_price_pending = false
        $this->assertTrue($shopStock->is_sellable);
        $this->assertFalse($shopStock->is_price_pending);
        $this->assertEquals(55000, (float)$shopStock->selling_price);

        // Checkout should now succeed
        $this->actingAs($this->seller);

        $response = $this->post(route('sales.store'), [
            'customer_name'  => 'Successful Customer',
            'payment_method' => 'cash',
            'items'          => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity'      => 1,
                    'price'         => 55000,
                ]
            ]
        ]);

        $response->assertRedirect();
        
        $saleItem = SaleItem::latest()->first();
        $this->assertNotNull($saleItem);
        $this->assertEquals(20000, (float)$saleItem->owner_cost_price);
        $this->assertEquals(48000, (float)$saleItem->owner_realized_sp);
        $this->assertEquals(48000, (float)$saleItem->shop_cost_price);
        $this->assertEquals(55000, (float)$saleItem->shop_realized_sp);

        // Verify Owner views the sales index with owner realized price (48,000) and not admin's selling price (55,000)
        $this->actingAs($this->owner);
        $response = $this->get(route('sales.index'));
        $response->assertSee('TZS 48,000');
        $response->assertDontSee('TZS 55,000');

        // Verify Owner views the sales show details page with owner realized price (48,000) and not admin's selling price (55,000)
        $response = $this->get(route('sales.show', $saleItem->sale_id));
        $response->assertSee('TZS 48,000');
        $response->assertDontSee('TZS 55,000');

        // Verify Owner views the sales report with owner realized price (48,000) and not admin's selling price (55,000)
        $response = $this->get(route('reports.sales'));
        $response->assertSee('TZS 48,000');
        $response->assertDontSee('TZS 55,000');

        // Verify Owner views the sales vs expenses report with owner realized price (48,000) and not admin's selling price (55,000)
        $response = $this->get(route('reports.sales-vs-expenses'));
        $response->assertSee('TZS 48,000');
        $response->assertDontSee('TZS 55,000');

        // Verify Admin views the sales show details page with admin's selling price (55,000) and not owner realized price (48,000)
        $this->actingAs($this->admin);
        $response = $this->get(route('sales.show', $saleItem->sale_id));
        $response->assertSee('TZS 55,000');
        $response->assertDontSee('TZS 48,000');
    }

    public function test_dependent_mode_keeps_original_behavior()
    {
        Setting::set('store_pricing_mode', 'DEPENDENT');

        // Create a ShopStock for the item
        $shopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 45000,
            'selling_price'      => 50000,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_sellable'        => true,
            'is_price_pending'   => false,
        ]);

        $this->actingAs($this->owner);

        // Update Main Store Selling Price
        $response = $this->put(route('main-stock.update', $this->mainStock), [
            'buying_price'  => 20000,
            'selling_price' => 48000,
            'date_received' => now()->toDateString(),
        ]);

        $shopStock->refresh();
        // Buying price should NOT be auto-updated in DEPENDENT mode
        $this->assertEquals(45000, (float)$shopStock->buying_price);
        // It is still sellable
        $this->assertTrue($shopStock->is_sellable);
        $this->assertTrue($shopStock->is_price_pending);
        $this->assertEquals(48000, (float)$shopStock->pending_selling_price);
    }

    public function test_shop_admin_can_access_reports_for_their_shop_only()
    {
        // 1. Create another shop and a sale for that shop
        $otherShop = Shop::create([
            'shop_name' => 'CityCenter Branch',
            'location'  => 'Posta',
        ]);

        $otherShopStock = ShopStock::create([
            'shop_id'            => $otherShop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 45000,
            'selling_price'      => 60000,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_sellable'        => true,
            'is_price_pending'   => false,
        ]);

        // Create sale for other shop
        $sale = Sale::create([
            'shop_id'        => $otherShop->id,
            'seller_id'      => $this->seller->id,
            'customer_name'  => 'Other Customer',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 60000,
        ]);

        $saleItem = SaleItem::create([
            'sale_id'           => $sale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 60000,
            'subtotal'          => 60000,
            'owner_cost_price'  => 20000,
            'owner_realized_sp' => 45000,
            'shop_cost_price'   => 45000,
            'shop_realized_sp'  => 60000,
        ]);

        // Create sale for admin's shop
        $mySale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->seller->id,
            'customer_name'  => 'My Customer',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 50000,
        ]);

        $mySaleItem = SaleItem::create([
            'sale_id'           => $mySale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 50000,
            'subtotal'          => 50000,
            'owner_cost_price'  => 20000,
            'owner_realized_sp' => 45000,
            'shop_cost_price'   => 45000,
            'shop_realized_sp'  => 50000,
        ]);

        // 2. Act as Shop Admin
        $this->actingAs($this->admin);

        // Sales report: Should see their own sale (50,000) but NOT other shop's sale (60,000)
        $response = $this->get(route('reports.sales'));
        $response->assertStatus(200);
        $response->assertSee('TZS 50,000');
        $response->assertDontSee('TZS 60,000');

        // Sales vs Expenses: Should see their own sales amount (50,000) but NOT other shop's (60,000)
        $response = $this->get(route('reports.sales-vs-expenses'));
        $response->assertStatus(200);
        $response->assertSee('TZS 50,000');
        $response->assertDontSee('TZS 60,000');

        // Stock report: Should see their shop stock details but not Main Warehouse Summary (type=main redirect or filtered out)
        $response = $this->get(route('reports.stock'));
        $response->assertStatus(200);
        $response->assertSee('LegacyInftech'); // Their shop
        $response->assertDontSee('Main Warehouse Stock Summary'); // Main store summary is hidden
    }
}
