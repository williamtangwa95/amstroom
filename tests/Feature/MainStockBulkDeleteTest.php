<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainStockBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Item $item1;
    protected Item $item2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner_ms_bulk@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $category = Category::create(['category_name' => 'Monitors']);

        $this->item1 = Item::create([
            'item_name'   => 'Dell UltraSharp 27',
            'category_id' => $category->id,
        ]);

        $this->item2 = Item::create([
            'item_name'   => 'LG Ergo 32',
            'category_id' => $category->id,
        ]);
    }

    public function test_owner_can_bulk_delete_unmodified_main_stock_batches()
    {
        $stock1 = MainStock::create([
            'item_id'            => $this->item1->id,
            'buying_price'       => 500000,
            'selling_price'      => 750000,
            'stocked_quantity'   => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
        ]);

        $stock2 = MainStock::create([
            'item_id'            => $this->item2->id,
            'buying_price'       => 800000,
            'selling_price'      => 1200000,
            'stocked_quantity'   => 3,
            'remaining_quantity' => 3,
            'date_received'      => now()->toDateString(),
        ]);

        $this->actingAs($this->owner);

        $response = $this->deleteJson(route('main-stock.bulk-destroy'), [
            'ids' => [$stock1->id, $stock2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('main_stocks', ['id' => $stock1->id]);
        $this->assertDatabaseMissing('main_stocks', ['id' => $stock2->id]);
    }

    public function test_bulk_delete_blocks_batches_that_have_been_transferred_or_sold()
    {
        $modifiedStock = MainStock::create([
            'item_id'            => $this->item1->id,
            'buying_price'       => 500000,
            'selling_price'      => 750000,
            'stocked_quantity'   => 5,
            'remaining_quantity' => 2, // 3 units transferred/sold
            'date_received'      => now()->toDateString(),
        ]);

        $this->actingAs($this->owner);

        $response = $this->deleteJson(route('main-stock.bulk-destroy'), [
            'ids' => [$modifiedStock->id],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('main_stocks', ['id' => $modifiedStock->id]);
    }
}
