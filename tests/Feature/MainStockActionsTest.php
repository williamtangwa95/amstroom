<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\MainStock;
use App\Models\StockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainStockActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Item $item;
    protected Category $category;

    /**
     * Set up tests, disabling CSRF token verification.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

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
        ]);

        $this->category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name'       => 'HP EliteBook 840 G8',
            'category_id'     => $this->category->id,
            'specification'   => 'Core i7, 16GB',
            'brand'           => 'HP',
            'model'           => 'EliteBook 840 G8',
            'warranty_period' => '1 Year',
        ]);
    }

    /** @test */
    public function test_owner_can_delete_main_stock_if_unmodified()
    {
        $this->actingAs($this->owner);

        $stock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now(),
        ]);

        $response = $this->delete(route('main-stock.destroy', $stock));

        $response->assertRedirect(route('main-stock.index'));
        $this->assertDatabaseMissing('main_stocks', ['id' => $stock->id]);
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->item->id,
            'transaction_type' => 'ADJUSTMENT',
            'notes'            => 'Stock batch deleted and removed from central warehouse.',
        ]);
    }

    /** @test */
    public function test_owner_cannot_delete_main_stock_if_partially_used()
    {
        $this->actingAs($this->owner);

        $stock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 9, // already transferred or sold
            'date_received'      => now(),
        ]);

        $response = $this->delete(route('main-stock.destroy', $stock));

        $response->assertRedirect(route('main-stock.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('main_stocks', ['id' => $stock->id]);
    }

    /** @test */
    public function test_non_owner_cannot_delete_main_stock()
    {
        $this->actingAs($this->admin);

        $stock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now(),
        ]);

        $response = $this->delete(route('main-stock.destroy', $stock));

        $response->assertStatus(403);
        $this->assertDatabaseHas('main_stocks', ['id' => $stock->id]);
    }

    /** @test */
    public function test_owner_can_update_remaining_quantity()
    {
        $this->actingAs($this->owner);

        $stock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now(),
        ]);

        $response = $this->put(route('main-stock.update', $stock), [
            'buying_price'       => 1000,
            'selling_price'      => 1200,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
        ]);

        $response->assertRedirect(route('main-stock.index'));
        $stock->refresh();
        $this->assertEquals(5, $stock->remaining_quantity);

        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->item->id,
            'transaction_type' => 'ADJUSTMENT',
            'notes'            => 'Stock batch remaining quantity adjusted from 10 to 5',
        ]);
    }
}
