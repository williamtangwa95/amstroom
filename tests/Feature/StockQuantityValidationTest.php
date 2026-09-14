<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockQuantityValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;
    protected User $owner;
    protected User $admin;
    protected Item $item;
    protected ShopStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create(['shop_name' => 'Main Shop', 'location' => 'Main Location']);

        $this->owner = User::create([
            'name' => 'Owner', 'email' => 'owner_qty@example.com', 'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin_qty@example.com', 'password' => bcrypt('password'),
            'role' => 'shop_admin', 'shop_id' => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name' => 'Test Laptop',
            'category_id' => $category->id,
            'is_admin_item' => false,
        ]);

        $this->stock = ShopStock::create([
            'shop_id' => $this->shop->id,
            'item_id' => $this->item->id,
            'buying_price' => 1000,
            'selling_price' => 1500,
            'quantity' => 9,
            'remaining_quantity' => 4,
            'low_stock_alert' => 1,
            'date_received' => now()->toDateString(),
            'is_admin_stock' => false,
        ]);
    }

    public function test_valid_quantity_update_passes()
    {
        $this->actingAs($this->owner);

        $response = $this->put(route('shop-stock.update', $this->stock), [
            'buying_price' => 1000,
            'selling_price' => 1500,
            'date_received' => now()->toDateString(),
            'quantity' => 9,
            'remaining_quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->quantity);
        $this->assertEquals(5, $this->stock->remaining_quantity);
    }

    public function test_zero_stocked_quantity_fails_validation()
    {
        $this->actingAs($this->owner);

        $response = $this->put(route('shop-stock.update', $this->stock), [
            'buying_price' => 1000,
            'selling_price' => 1500,
            'date_received' => now()->toDateString(),
            'quantity' => 0,
            'remaining_quantity' => 0,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->quantity);
    }

    public function test_remaining_quantity_greater_than_stocked_quantity_fails_validation()
    {
        $this->actingAs($this->owner);

        $response = $this->put(route('shop-stock.update', $this->stock), [
            'buying_price' => 1000,
            'selling_price' => 1500,
            'date_received' => now()->toDateString(),
            'quantity' => 5,
            'remaining_quantity' => 8,
        ]);

        $response->assertSessionHasErrors(['quantity', 'remaining_quantity']);
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->quantity);
        $this->assertEquals(4, $this->stock->remaining_quantity);
    }
}
