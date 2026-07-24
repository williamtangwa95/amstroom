<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDirectSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_sell_directly_from_main_store_and_deduct_stock()
    {
        // Create Owner user
        $owner = User::create([
            'name' => 'System Owner',
            'email' => 'owner@amstroom.com',
            'phone' => '+255700000001',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'shop_id' => null,
        ]);

        // Create Category and Item
        $category = Category::create([
            'category_name' => 'Test Laptop',
            'description' => 'Test',
        ]);
        
        $item = Item::create([
            'item_name' => 'Super Laptop',
            'category_id' => $category->id,
            'brand' => 'BrandX',
            'model' => 'MX-1',
            'buying_price' => 1000,
            'selling_price' => 1500,
        ]);

        // Add main stock
        $mainStock = MainStock::create([
            'item_id' => $item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'stocked_quantity' => 10,
            'remaining_quantity' => 10,
            'date_received' => now()->toDateString(),
        ]);

        // Act: Send POST request as owner to complete a sale
        $response = $this->actingAs($owner)->post(route('sales.store'), [
            'customer_name' => 'Direct Customer',
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $mainStock->id,
                    'quantity' => 3,
                    'price' => 1500,
                ]
            ]
        ]);

        // Assert: Redirect to receipt
        $response->assertRedirect();
        
        // Assert stock is decremented
        $mainStock->refresh();
        $this->assertEquals(7, $mainStock->remaining_quantity);

        // Assert sale is created with null shop_id
        $sale = Sale::latest()->first();
        $this->assertNull($sale->shop_id);
        $this->assertEquals($owner->id, $sale->seller_id);
        $this->assertEquals(4500, $sale->total_amount);

        // Assert stock log is created
        $log = StockLog::latest()->first();
        $this->assertEquals('Main Store', $log->from_location);
        $this->assertEquals(3, $log->quantity);
    }

    public function test_admin_receives_stock_transfer_with_custom_selling_price()
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner2@amstroom.com', 'password' => bcrypt('password'), 'role' => 'owner']);
        $shop = \App\Models\Shop::create(['shop_name' => 'Test Branch', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin3@amstroom.com', 'password' => bcrypt('password'), 'role' => 'shop_admin', 'shop_id' => $shop->id]);
        
        $category = Category::create(['category_name' => 'Category', 'description' => 'Test']);
        $item = Item::create(['item_name' => 'Mouse', 'category_id' => $category->id, 'brand' => 'A', 'model' => 'B', 'buying_price' => 2000, 'selling_price' => 5000]);

        $transfer = StockTransfer::create([
            'from_store' => 'Main Warehouse',
            'to_shop' => $shop->id,
            'approved_by' => $owner->id,
            'transfer_date' => now()->toDateString(),
            'status' => 'pending_receipt',
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'buying_price' => 2000,
            'selling_price' => 5000, // owner selling price
            'status' => 'pending',
        ]);

        // Act: Admin confirms receipt and sets custom selling price to 7000
        $response = $this->actingAs($admin)->post(route('stock-transfers.approve-item', $transferItem), [
            'selling_price' => 7000,
        ]);

        $response->assertRedirect();
        
        // Assert ShopStock inherits owner's selling price as buying price, and admin's selling price
        $shopStock = \App\Models\ShopStock::where('shop_id', $shop->id)->where('item_id', $item->id)->first();
        $this->assertNotNull($shopStock);
        $this->assertEquals(5000, $shopStock->buying_price); // Inherited from owner's selling price
        $this->assertEquals(7000, $shopStock->selling_price); // Admin decided selling price
    }

    public function test_sale_price_cannot_be_less_than_standard_price()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Test Branch', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $seller = User::create(['name' => 'Seller', 'email' => 'seller4@amstroom.com', 'password' => bcrypt('password'), 'role' => 'seller', 'shop_id' => $shop->id]);
        
        $category = Category::create(['category_name' => 'Category', 'description' => 'Test']);
        $item = Item::create(['item_name' => 'Mouse', 'category_id' => $category->id, 'brand' => 'A', 'model' => 'B', 'buying_price' => 2000, 'selling_price' => 5000]);

        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 5000,
            'selling_price' => 7000,
            'quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        // Act: Attempt to sell at 6500 (lower than standard 7000)
        $response = $this->actingAs($seller)->post(route('sales.store'), [
            'customer_name' => 'Walk-in',
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity' => 1,
                    'price' => 6500, // Invalid! Less than 7000
                ]
            ]
        ]);

        // Assert it throws an exception / fails (custom check in controller throws Exception, handled as 500 or validation exception depending on how exception handling is set, but should not redirect to success)
        $this->assertTrue(session()->has('errors') || $response->status() >= 400 || $response->status() == 500);

        // Act: Sell at 7500 (valid negotiated price)
        $response2 = $this->actingAs($seller)->post(route('sales.store'), [
            'customer_name' => 'Walk-in',
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity' => 1,
                    'price' => 7500, // Valid! Greater than 7000
                ]
            ]
        ]);

        $response2->assertRedirect();
        
        $sale = Sale::latest()->first();
        $this->assertEquals(7500, $sale->total_amount);
    }

    public function test_sale_redirects_based_on_printer_enabled_setting()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Test Branch', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $seller = User::create(['name' => 'Seller', 'email' => 'seller5@amstroom.com', 'password' => bcrypt('password'), 'role' => 'seller', 'shop_id' => $shop->id]);
        
        $category = Category::create(['category_name' => 'Category', 'description' => 'Test']);
        $item = Item::create(['item_name' => 'Mouse', 'category_id' => $category->id, 'brand' => 'A', 'model' => 'B', 'buying_price' => 2000, 'selling_price' => 5000]);

        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 5000,
            'selling_price' => 7000,
            'quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        // Scenario 1: Printer is Enabled ('1') -> should redirect to sales.receipt
        \App\Models\Setting::set('printer_enabled_user_' . $seller->id, '1');

        $response = $this->actingAs($seller)->post(route('sales.store'), [
            'customer_name' => 'Walk-in',
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity' => 1,
                    'price' => 7000,
                ]
            ]
        ]);

        $sale1 = Sale::latest()->first();
        $response->assertRedirect(route('sales.receipt', $sale1->id));

        // Scenario 2: Printer is Disabled ('0') -> should redirect to sales.index
        \App\Models\Setting::set('printer_enabled_user_' . $seller->id, '0');

        $response2 = $this->actingAs($seller)->post(route('sales.store'), [
            'customer_name' => 'Walk-in',
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $shopStock->id,
                    'quantity' => 1,
                    'price' => 7000,
                ]
            ]
        ]);

        $response2->assertRedirect(route('sales.index'));
    }

    public function test_admin_can_update_shop_branding_and_printer_settings()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Branch Alpha', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin_settings@amstroom.com', 'password' => bcrypt('password'), 'role' => 'shop_admin', 'shop_id' => $shop->id]);

        // Load settings page
        $response = $this->actingAs($admin)->get(route('settings.index'));
        $response->assertStatus(200);

        // Update settings
        $response2 = $this->actingAs($admin)->post(route('settings.update'), [
            'system_name' => 'Branch Alpha Renamed',
            'slogan' => 'New shop slogan',
            'printer_enabled' => '0',
        ]);

        $response2->assertRedirect();

        $shop->refresh();
        $this->assertEquals('Branch Alpha Renamed', $shop->shop_name);
        $this->assertEquals('New shop slogan', $shop->slogan);
        $this->assertEquals('0', \App\Models\Setting::get('printer_enabled_user_' . $admin->id));
    }

    public function test_seller_can_only_update_printer_settings()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Branch Alpha', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $seller = User::create(['name' => 'Seller', 'email' => 'seller_settings@amstroom.com', 'password' => bcrypt('password'), 'role' => 'seller', 'shop_id' => $shop->id]);

        // Load settings page
        $response = $this->actingAs($seller)->get(route('settings.index'));
        $response->assertStatus(200);

        // Update printer setting
        $response2 = $this->actingAs($seller)->post(route('settings.update'), [
            'printer_enabled' => '0',
        ]);

        $response2->assertRedirect();
        $this->assertEquals('0', \App\Models\Setting::get('printer_enabled_user_' . $seller->id));

        // Shop should NOT be changed since sellers don't have branding fields
        $shop->refresh();
        $this->assertEquals('Branch Alpha', $shop->shop_name);
    }

    public function test_admin_and_owner_can_update_shop_stock_selling_price()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Branch Alpha', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin_price@amstroom.com', 'password' => bcrypt('password'), 'role' => 'shop_admin', 'shop_id' => $shop->id]);
        $owner = User::create(['name' => 'Owner', 'email' => 'owner_price@amstroom.com', 'password' => bcrypt('password'), 'role' => 'owner']);
        $category = \App\Models\Category::create(['category_name' => 'IT']);
        $item = \App\Models\Item::create(['item_name' => 'Dell Laptop', 'category_id' => $category->id]);
        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        // 1. Admin updates price -> should be pending
        $response = $this->actingAs($admin)->patch(route('shop-stock.update-price', $shopStock), [
            'selling_price' => 1700
        ]);

        $response->assertRedirect();
        $shopStock->refresh();
        $this->assertEquals(1500, $shopStock->selling_price); // Still old price
        $this->assertTrue($shopStock->is_price_pending);
        $this->assertEquals(1700, $shopStock->pending_selling_price);

        // 2. Owner approves price -> should be approved directly
        $responseApprove = $this->actingAs($owner)->post(route('shop-stock.approve-price', $shopStock));
        $responseApprove->assertRedirect();
        $shopStock->refresh();
        $this->assertEquals(1700, $shopStock->selling_price); // Updated to approved price
        $this->assertFalse($shopStock->is_price_pending);

        // 3. Owner directly updates price -> should be approved immediately
        $responseOwnerUpdate = $this->actingAs($owner)->patch(route('shop-stock.update-price', $shopStock), [
            'selling_price' => 1900
        ]);
        $responseOwnerUpdate->assertRedirect();
        $shopStock->refresh();
        $this->assertEquals(1900, $shopStock->selling_price);
        $this->assertFalse($shopStock->is_price_pending);
    }

    public function test_selling_price_update_cannot_be_less_than_buying_price()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Branch Alpha', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin_price_invalid@amstroom.com', 'password' => bcrypt('password'), 'role' => 'shop_admin', 'shop_id' => $shop->id]);
        $category = \App\Models\Category::create(['category_name' => 'IT']);
        $item = \App\Models\Item::create(['item_name' => 'Dell Laptop', 'category_id' => $category->id]);
        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'quantity' => 10,
            'remaining_quantity' => 10,
        ]);

        $response = $this->actingAs($admin)->patch(route('shop-stock.update-price', $shopStock), [
            'selling_price' => 900
        ]);

        $response->assertSessionHasErrors('selling_price');
        $shopStock->refresh();
        $this->assertEquals(1500, $shopStock->selling_price);
    }

    public function test_owner_updating_main_stock_makes_related_shop_stocks_pending_approved_by_admin()
    {
        $shop = \App\Models\Shop::create(['shop_name' => 'Branch Alpha', 'location' => 'Loc', 'phone' => '123', 'status' => 'active']);
        $owner = User::create(['name' => 'Owner', 'email' => 'owner_main_price@amstroom.com', 'password' => bcrypt('password'), 'role' => 'owner']);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin_main_price@amstroom.com', 'password' => bcrypt('password'), 'role' => 'shop_admin', 'shop_id' => $shop->id]);
        $category = \App\Models\Category::create(['category_name' => 'IT']);
        $item = \App\Models\Item::create(['item_name' => 'Dell Laptop', 'category_id' => $category->id]);
        
        $mainStock = \App\Models\MainStock::create([
            'item_id' => $item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'stocked_quantity' => 20,
            'remaining_quantity' => 20,
            'date_received' => now()->toDateString(),
        ]);

        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'date_received' => now()->toDateString(),
        ]);

        // 1. Owner updates price on MainStock
        $response = $this->actingAs($owner)->put(route('main-stock.update', $mainStock), [
            'buying_price' => 1000,
            'selling_price' => 1800,
            'date_received' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        
        // 2. MainStock price should be directly updated
        $mainStock->refresh();
        $this->assertEquals(1800, $mainStock->selling_price);
        $this->assertFalse($mainStock->is_price_pending);

        // 3. ShopStock price should become pending Owner's new price
        $shopStock->refresh();
        $this->assertEquals(1500, $shopStock->selling_price);
        $this->assertTrue($shopStock->is_price_pending);
        $this->assertEquals(1800, $shopStock->pending_selling_price);

        // 4. Shop Admin approves price update with custom selling price
        $responseApprove = $this->actingAs($admin)->post(route('shop-stock.approve-price', $shopStock), [
            'selling_price' => 1950
        ]);
        $responseApprove->assertRedirect();

        // 5. ShopStock should now be approved and updated to custom price
        $shopStock->refresh();
        $this->assertEquals(1950, $shopStock->selling_price);
        $this->assertFalse($shopStock->is_price_pending);
    }
}
