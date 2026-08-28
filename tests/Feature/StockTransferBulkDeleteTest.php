<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Kariakoo Branch',
            'location'  => 'Kariakoo',
        ]);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner_bulk@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin_bulk@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name'   => 'MacBook Pro 16',
            'category_id' => $category->id,
        ]);

        MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 2000000,
            'selling_price'      => 3000000,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);
    }

    public function test_owner_can_bulk_delete_stock_transfers_and_restore_stock()
    {
        // Create 2 transfers
        $transfer1 = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        StockTransferItem::create([
            'transfer_id'   => $transfer1->id,
            'item_id'       => $this->item->id,
            'quantity'      => 3,
            'buying_price'  => 2000000,
            'selling_price' => 3000000,
            'status'        => 'pending',
        ]);

        $transfer2 = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        StockTransferItem::create([
            'transfer_id'   => $transfer2->id,
            'item_id'       => $this->item->id,
            'quantity'      => 2,
            'buying_price'  => 2000000,
            'selling_price' => 3000000,
            'status'        => 'pending',
        ]);

        $this->actingAs($this->owner);

        $response = $this->delete(route('stock-transfers.bulk-destroy'), [
            'ids' => [$transfer1->id, $transfer2->id],
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('success');

        // Transfers and items deleted
        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer1->id]);
        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer2->id]);

        // Stock restored back to MainStock (10 + 3 + 2 = 15)
        $this->assertDatabaseHas('main_stocks', [
            'item_id'            => $this->item->id,
            'remaining_quantity' => 15,
        ]);
    }
}
