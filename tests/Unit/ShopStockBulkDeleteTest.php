<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopStockBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_blocks_when_stock_has_sales_or_modified_quantity()
    {
        $owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner_bulk@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        $shop = Shop::create([
            'shop_name' => 'Main Test Shop',
            'location'  => 'Dar es Salaam',
            'is_active' => true,
        ]);

        $category = Category::create([
            'category_name' => 'Electronics',
        ]);

        $item = Item::create([
            'item_name'   => 'Adapter 12V',
            'category_id' => $category->id,
        ]);

        // Stock A (quantity 10, remaining 10 -> valid to delete)
        $stockA = ShopStock::create([
            'shop_id'            => $shop->id,
            'item_id'            => $item->id,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'buying_price'       => 15000,
            'selling_price'      => 20000,
            'is_admin_stock'     => true,
        ]);

        // Stock B (quantity 10, remaining 5 -> HAS SALES, invalid to delete)
        $stockB = ShopStock::create([
            'shop_id'            => $shop->id,
            'item_id'            => $item->id,
            'quantity'           => 10,
            'remaining_quantity' => 5,
            'buying_price'       => 15000,
            'selling_price'      => 20000,
            'is_admin_stock'     => true,
        ]);

        // Request bulk deletion of both stock A and stock B
        $response = $this->actingAs($owner)->deleteJson(route('shop-stock.bulk-destroy'), [
            'ids' => [$stockA->id, $stockB->id],
        ]);

        // Should return 422 Unprocessable Entity and block bulk delete
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'errors' => [
                "Item 'Adapter 12V' (Batch #{$stockB->id}): 5 unit(s) have already been sold or modified.",
            ],
        ]);

        // Verify stock A was NOT deleted due to strict atomic validation
        $this->assertDatabaseHas('shop_stocks', ['id' => $stockA->id]);
        $this->assertDatabaseHas('shop_stocks', ['id' => $stockB->id]);
    }
}
