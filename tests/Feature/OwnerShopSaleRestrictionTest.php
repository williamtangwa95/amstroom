<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Sale;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerShopSaleRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_is_restricted_from_seeing_and_executing_actions_on_shop_sales()
    {
        $owner = User::create([
            'name' => 'Store Owner',
            'email' => 'owner@amstroom.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $shop = Shop::create([
            'shop_name' => 'Branch Shop 1',
            'location' => 'Town Center',
        ]);

        $seller = User::create([
            'name' => 'Shop Seller',
            'email' => 'seller@amstroom.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shop_id' => $shop->id,
        ]);

        $category = Category::create(['category_name' => 'Gadgets']);
        $item = Item::create([
            'item_name' => 'Headphones',
            'category_id' => $category->id,
            'buying_price' => 50,
            'selling_price' => 100,
        ]);

        // Create a shop sale committed by seller
        $shopSale = Sale::create([
            'shop_id' => $shop->id,
            'seller_id' => $seller->id,
            'customer_name' => 'Shop Customer',
            'total_amount' => 100,
            'payment_method' => 'cash',
            'sale_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        // Create an owner sale committed by owner
        $ownerSale = Sale::create([
            'shop_id' => null,
            'seller_id' => $owner->id,
            'customer_name' => 'Owner Customer',
            'total_amount' => 100,
            'payment_method' => 'cash',
            'sale_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        // 1. Verify Datatables JSON endpoint response as Owner
        $response = $this->actingAs($owner)->getJson(route('sales.data'));
        $response->assertStatus(200);
        $data = $response->json('data');

        $shopSaleRow = collect($data)->firstWhere('sale_id', '#SL-' . $shopSale->id);
        $ownerSaleRow = collect($data)->firstWhere('sale_id', '#SL-' . $ownerSale->id);

        $this->assertNotNull($shopSaleRow);
        $this->assertNotNull($ownerSaleRow);

        // Shop sale row for Owner must NOT contain action buttons or edit customer button
        $this->assertStringNotContainsString('edit-customer-btn', $shopSaleRow['actions']);
        $this->assertStringNotContainsString('edit-customer-btn', $shopSaleRow['customer']);
        $this->assertStringNotContainsString('Print Invoice', $shopSaleRow['actions']);
        $this->assertStringNotContainsString('Print Proforma', $shopSaleRow['actions']);
        $this->assertStringNotContainsString('Print Delivery Note', $shopSaleRow['actions']);
        $this->assertStringNotContainsString('Print Receipt', $shopSaleRow['actions']);

        // Owner sale row for Owner MUST contain action buttons
        $this->assertStringContainsString('edit-customer-btn', $ownerSaleRow['actions']);
        $this->assertStringContainsString('edit-customer-btn', $ownerSaleRow['customer']);
        $this->assertStringContainsString('Print Invoice', $ownerSaleRow['actions']);
        $this->assertStringContainsString('Print Proforma', $ownerSaleRow['actions']);
        $this->assertStringContainsString('Print Delivery Note', $ownerSaleRow['actions']);
        $this->assertStringContainsString('Print Receipt', $ownerSaleRow['actions']);

        // 2. Verify direct backend endpoint access as Owner for shop sale is forbidden (403)
        $this->actingAs($owner)->get(route('sales.invoice', $shopSale))->assertStatus(403);
        $this->actingAs($owner)->get(route('sales.proforma', $shopSale))->assertStatus(403);
        $this->actingAs($owner)->get(route('sales.delivery-note', $shopSale))->assertStatus(403);
        $this->actingAs($owner)->get(route('sales.receipt', $shopSale))->assertStatus(403);
        $this->actingAs($owner)->patch(route('sales.update-customer', $shopSale), ['customer_name' => 'New Name'])->assertStatus(403);
        $this->actingAs($owner)->get(route('sales-returns.create', $shopSale))->assertStatus(403);

        // Verify Owner show view does not contain return button for shop sale
        $showResponse = $this->actingAs($owner)->get(route('sales.show', $shopSale));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('Return Items / Refund');

        // 3. Verify Seller/Shop Staff CAN access endpoints for shop sale
        $this->actingAs($seller)->get(route('sales.invoice', $shopSale))->assertStatus(200);
        $this->actingAs($seller)->get(route('sales.proforma', $shopSale))->assertStatus(200);
        $this->actingAs($seller)->get(route('sales.delivery-note', $shopSale))->assertStatus(200);
        $this->actingAs($seller)->get(route('sales.receipt', $shopSale))->assertStatus(200);
        $this->actingAs($seller)->patch(route('sales.update-customer', $shopSale), ['customer_name' => 'Updated Name'])->assertStatus(302);
        $this->actingAs($seller)->get(route('sales-returns.create', $shopSale))->assertStatus(200);
    }

    public function test_owner_is_restricted_from_seeing_and_modifying_shop_stock_controls_on_show_page()
    {
        $owner = User::create([
            'name' => 'Store Owner',
            'email' => 'owner2@amstroom.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $shop = Shop::create([
            'shop_name' => 'Branch Shop 2',
            'location' => 'City Center',
        ]);

        $category = Category::create(['category_name' => 'Tech']);
        $item = Item::create([
            'item_name' => 'Smartphone',
            'category_id' => $category->id,
            'buying_price' => 200,
            'selling_price' => 300,
        ]);

        $shopStock = \App\Models\ShopStock::create([
            'shop_id' => $shop->id,
            'item_id' => $item->id,
            'buying_price' => 200,
            'selling_price' => 300,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'low_stock_alert' => 2,
            'is_admin_stock' => false, // Stock from shop
        ]);

        // 1. Owner view of shop stock details page must not see restricted controls or Shop Retail Price
        $response = $this->actingAs($owner)->get(route('shop-stock.show', $shopStock));
        $response->assertStatus(200);
        $response->assertDontSee('Shop Retail Price');
        $response->assertDontSee('Upload/Change Product Photo');
        $response->assertDontSee('Update Low Stock Alert Threshold');
        $response->assertDontSee('Update Item Selling Price');
        $response->assertDontSee('Custom Components on Sell');
        $response->assertDontSee('Delete Stock Batch');

        // 2. Direct backend requests by Owner for shop stock must be forbidden (403)
        $this->actingAs($owner)->patch(route('shop-stock.update-alert', $shopStock), ['low_stock_alert' => 5])->assertStatus(403);
        $this->actingAs($owner)->patch(route('shop-stock.update-price', $shopStock), ['selling_price' => 350])->assertStatus(403);
        $this->actingAs($owner)->post(route('settings.toggle-components'), ['shop_stock_id' => $shopStock->id, 'enabled' => 1])->assertStatus(403);
        $this->actingAs($owner)->delete(route('shop-stock.destroy', $shopStock))->assertStatus(403);
    }
}
