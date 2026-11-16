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

class ItemDeleteRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Shop $shop;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Main Branch',
            'location'  => 'City Center',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->category = Category::create([
            'category_name' => 'Electronics',
            'description'   => 'Electronic goods',
        ]);
    }

    public function test_item_without_stock_has_enabled_delete_button_and_can_be_deleted(): void
    {
        $item = Item::create([
            'item_name'   => 'Wireless Keyboard Standalone',
            'category_id' => $this->category->id,
            'brand'       => 'Logitech',
            'model'       => 'K380',
        ]);

        // Check data table shows active delete button
        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('del-item-' . $item->id, $data[0]['actions']);
        $this->assertStringNotContainsString('disabled', $data[0]['actions']);

        // Check deleting succeeds
        $delResponse = $this->actingAs($this->owner)->delete(route('items.destroy', $item->id));
        $delResponse->assertRedirect(route('items.index'));
        $delResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_item_with_main_store_stock_has_disabled_delete_button_and_blocks_delete(): void
    {
        $item = Item::create([
            'item_name'   => 'HP Laptop EliteBook',
            'category_id' => $this->category->id,
            'brand'       => 'HP',
            'model'       => '840 G8',
        ]);

        MainStock::create([
            'item_id'            => $item->id,
            'buying_price'       => 1000000,
            'selling_price'      => 1300000,
            'stocked_quantity'   => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
        ]);

        // Check data table shows disabled delete button with reason
        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('disabled', $data[0]['actions']);
        $this->assertStringContainsString('Cannot delete: product has main store stock', $data[0]['actions']);

        // Check direct DELETE request is blocked
        $delResponse = $this->actingAs($this->owner)->delete(route('items.destroy', $item->id));
        $delResponse->assertRedirect(route('items.index'));
        $delResponse->assertSessionHas('error');

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_item_with_shop_stock_has_disabled_delete_button_and_blocks_delete(): void
    {
        $item = Item::create([
            'item_name'   => 'Dell Latitude Laptop',
            'category_id' => $this->category->id,
            'brand'       => 'Dell',
            'model'       => '5420',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item->id,
            'quantity'           => 3,
            'remaining_quantity' => 3,
            'is_admin_stock'     => false,
        ]);

        // Check data table shows disabled delete button
        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('disabled', $data[0]['actions']);
        $this->assertStringContainsString('Cannot delete: product has shop stock', $data[0]['actions']);

        // Check direct DELETE request is blocked
        $delResponse = $this->actingAs($this->owner)->delete(route('items.destroy', $item->id));
        $delResponse->assertRedirect(route('items.index'));
        $delResponse->assertSessionHas('error');

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_item_with_shop_stock_0_over_3_cannot_be_deleted(): void
    {
        $item = Item::create([
            'item_name'   => 'HP Wireless Mouse',
            'category_id' => $this->category->id,
            'brand'       => 'HP',
            'model'       => 'X3000',
        ]);

        // 0 remaining out of 3 total (0/3)
        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item->id,
            'quantity'           => 3,
            'remaining_quantity' => 0,
            'is_admin_stock'     => false,
        ]);

        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('disabled', $data[0]['actions']);
        $this->assertStringContainsString('Cannot delete: product has shop stock (0/3)', $data[0]['actions']);

        // Check delete is blocked
        $delResponse = $this->actingAs($this->owner)->delete(route('items.destroy', $item->id));
        $delResponse->assertRedirect(route('items.index'));
        $delResponse->assertSessionHas('error');

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_item_with_shop_stock_0_over_0_can_be_deleted(): void
    {
        $item = Item::create([
            'item_name'   => 'Dell Optical Mouse',
            'category_id' => $this->category->id,
            'brand'       => 'Dell',
            'model'       => 'MS116',
        ]);

        // 0 remaining out of 0 total (0/0)
        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item->id,
            'quantity'           => 0,
            'remaining_quantity' => 0,
            'is_admin_stock'     => false,
        ]);

        // Check data table shows active delete button (not disabled)
        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringNotContainsString('disabled', $data[0]['actions']);
        $this->assertStringContainsString('del-item-' . $item->id, $data[0]['actions']);

        // Check delete succeeds
        $delResponse = $this->actingAs($this->owner)->delete(route('items.destroy', $item->id));
        $delResponse->assertRedirect(route('items.index'));
        $delResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_item_with_both_stocks_shows_combined_tooltip_and_blocks_json_delete(): void
    {
        $item = Item::create([
            'item_name'   => 'Samsung Monitor 24',
            'category_id' => $this->category->id,
            'brand'       => 'Samsung',
            'model'       => 'S24F350',
        ]);

        MainStock::create([
            'item_id'            => $item->id,
            'buying_price'       => 200000,
            'selling_price'      => 260000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item->id,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_admin_stock'     => false,
        ]);

        // Check data table shows both reasons in tooltip
        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('has main store stock and has shop stock', $data[0]['actions']);

        // Check JSON delete request returns 422
        $delResponse = $this->actingAs($this->owner)->deleteJson(route('items.destroy', $item->id));
        $delResponse->assertStatus(422)
                    ->assertJson(['success' => false]);

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_shop_stock_column_shows_available_over_total_stock(): void
    {
        $item = Item::create([
            'item_name'   => 'HP Display Monitor',
            'category_id' => $this->category->id,
            'brand'       => 'HP',
            'model'       => 'P201',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item->id,
            'quantity'           => 9,
            'remaining_quantity' => 1,
            'is_admin_stock'     => false,
        ]);

        $response = $this->actingAs($this->owner)->get(route('items.data'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Verify shop_stock field is present and formatted as "1 available / 9 total"
        $this->assertArrayHasKey('shop_stock', $data[0]);
        $this->assertStringContainsString('1 available / 9 total', $data[0]['shop_stock']);
    }
}
