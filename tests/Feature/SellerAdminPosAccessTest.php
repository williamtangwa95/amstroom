<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerAdminPosAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Shop $shop1;
    protected Shop $shop2;
    protected User $shopAdmin;
    protected User $seller;
    protected User $otherShopSeller;
    protected Item $item;
    protected ShopStock $shopStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop1 = Shop::create([
            'shop_name' => 'Shop One',
            'location' => 'Dar es Salaam',
            'status' => 'active',
        ]);

        $this->shop2 = Shop::create([
            'shop_name' => 'Shop Two',
            'location' => 'Arusha',
            'status' => 'active',
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
            'shop_id'  => $this->shop1->id,
        ]);

        $this->seller = User::create([
            'name'     => 'Seller User',
            'email'    => 'seller@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop1->id,
            'allow_pos_admin' => false,
        ]);

        $this->otherShopSeller = User::create([
            'name'     => 'Other Seller',
            'email'    => 'otherseller@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop2->id,
            'allow_pos_admin' => false,
        ]);

        $category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name' => 'Dell Latitude',
            'category_id' => $category->id,
            'brand' => 'Dell',
        ]);

        $this->shopStock = ShopStock::create([
            'shop_id' => $this->shop1->id,
            'item_id' => $this->item->id,
            'buying_price' => 500000,
            'selling_price' => 700000,
            'remaining_quantity' => 10,
            'is_sellable' => true,
        ]);
    }

    public function test_can_access_admin_pos_helper_logic()
    {
        $this->assertTrue($this->owner->canAccessAdminPos());
        $this->assertTrue($this->shopAdmin->canAccessAdminPos());

        $this->assertFalse($this->seller->canAccessAdminPos());

        $this->seller->update(['allow_pos_admin' => true]);
        $this->assertTrue($this->seller->canAccessAdminPos());
    }

    public function test_shop_admin_can_toggle_pos_admin_for_own_seller()
    {
        $this->actingAs($this->shopAdmin);

        $response = $this->patchJson(route('users.toggle-pos-admin', $this->seller));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'allow_pos_admin' => true,
        ]);

        $this->seller->refresh();
        $this->assertTrue((bool) $this->seller->allow_pos_admin);

        // Toggle back
        $response = $this->patchJson(route('users.toggle-pos-admin', $this->seller));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'allow_pos_admin' => false,
        ]);

        $this->seller->refresh();
        $this->assertFalse((bool) $this->seller->allow_pos_admin);
    }

    public function test_shop_admin_cannot_toggle_seller_from_another_shop()
    {
        $this->actingAs($this->shopAdmin);

        $response = $this->patch(route('users.toggle-pos-admin', $this->otherShopSeller));
        $response->assertStatus(403);
    }

    public function test_owner_can_toggle_any_seller()
    {
        $this->actingAs($this->owner);

        $response = $this->patchJson(route('users.toggle-pos-admin', $this->otherShopSeller));
        $response->assertOk();
        $response->assertJson(['success' => true, 'allow_pos_admin' => true]);

        $this->otherShopSeller->refresh();
        $this->assertTrue((bool) $this->otherShopSeller->allow_pos_admin);
    }

    public function test_users_data_endpoint_contains_admin_pos_toggle()
    {
        $this->actingAs($this->shopAdmin);

        $response = $this->get(route('users.data'));
        $response->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Check that pos-admin-toggle switch HTML is included
        $hasToggle = false;
        foreach ($data as $row) {
            if (isset($row['admin_pos']) && str_contains($row['admin_pos'], 'pos-admin-toggle')) {
                $hasToggle = true;
                break;
            }
        }
        $this->assertTrue($hasToggle);
    }

    public function test_seller_without_admin_pos_cannot_sell_below_selling_price()
    {
        $this->actingAs($this->seller);

        // Selling price is 700,000; buying price is 500,000
        // Attempting to sell at 600,000 should throw Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot be less than dedicated selling price/');

        $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $this->shopStock->id,
                    'quantity' => 1,
                    'price' => 600000,
                ]
            ]
        ]);
    }

    public function test_seller_with_admin_pos_can_sell_at_negotiated_price_down_to_buying_price()
    {
        $this->seller->update(['allow_pos_admin' => true]);
        $this->actingAs($this->seller);

        // Selling at 600,000 (below selling price 700,000 but above buying price 500,000)
        $response = $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $this->shopStock->id,
                    'quantity' => 1,
                    'price' => 600000,
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', [
            'seller_id' => $this->seller->id,
            'total_amount' => 600000,
        ]);
    }

    public function test_seller_with_admin_pos_cannot_sell_below_buying_price()
    {
        $this->seller->update(['allow_pos_admin' => true]);
        $this->actingAs($this->seller);

        // Selling at 400,000 (below buying price 500,000) should still fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot be less than buying price/');

        $this->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                [
                    'shop_stock_id' => $this->shopStock->id,
                    'quantity' => 1,
                    'price' => 400000,
                ]
            ]
        ]);
    }
}
