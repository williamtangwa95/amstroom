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

class StockTransferRejectionAndOwnerModificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $shopAdmin;
    protected Shop $shop;
    protected Item $item1;
    protected Item $item2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Test Branch',
            'location'  => 'Downtown',
        ]);

        $this->owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shopAdmin = User::create([
            'name'     => 'Shop Admin User',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Electronics']);

        $this->item1 = Item::create([
            'category_id' => $category->id,
            'item_name'   => 'iPhone 15',
            'brand'       => 'Apple',
            'model'       => 'Standard',
        ]);

        $this->item2 = Item::create([
            'category_id' => $category->id,
            'item_name'   => 'Samsung S24',
            'brand'       => 'Samsung',
            'model'       => 'Ultra',
        ]);

        MainStock::create([
            'item_id'            => $this->item1->id,
            'buying_price'       => 800,
            'selling_price'      => 1000,
            'stocked_quantity'   => 50,
            'remaining_quantity' => 50,
            'date_received'      => now(),
        ]);

        MainStock::create([
            'item_id'            => $this->item2->id,
            'buying_price'       => 700,
            'selling_price'      => 900,
            'stocked_quantity'   => 30,
            'remaining_quantity' => 30,
            'date_received'      => now(),
        ]);
    }

    public function test_admin_can_reject_transfer_item_with_reason()
    {
        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id'   => $transfer->id,
            'item_id'       => $this->item1->id,
            'quantity'      => 10,
            'buying_price'  => 800,
            'selling_price' => 1000,
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->shopAdmin)
            ->post(route('stock-transfers.reject-item', $transferItem), [
                'rejection_reason' => 'Damaged outer box during transit',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfer_items', [
            'id'               => $transferItem->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Damaged outer box during transit',
            'rejected_by'      => $this->shopAdmin->id,
        ]);

        $this->assertEquals('rejected', $transfer->fresh()->status);
    }

    public function test_owner_can_update_rejected_transfer_item_quantity_and_restore_stock()
    {
        // Initial main stock: 50. We transfer 10. Main stock remaining = 40.
        $this->item1->mainStocks()->first()->update(['remaining_quantity' => 40]);

        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'rejected',
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id'      => $transfer->id,
            'item_id'          => $this->item1->id,
            'quantity'         => 10,
            'buying_price'     => 800,
            'selling_price'    => 1000,
            'status'           => 'rejected',
            'rejection_reason' => 'Mismatch in quantity',
        ]);

        // Owner updates quantity down from 10 to 6. Surplus 4 stock should return to MainStock (40 + 4 = 44).
        $response = $this->actingAs($this->owner)
            ->put(route('stock-transfers.update-item', $transferItem), [
                'item_id'  => $this->item1->id,
                'quantity' => 6,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfer_items', [
            'id'               => $transferItem->id,
            'quantity'         => 6,
            'status'           => 'pending',
            'rejection_reason' => null,
        ]);

        $this->assertEquals(44, $this->item1->mainStocks()->first()->remaining_quantity);
    }

    public function test_owner_can_delete_transfer_item_and_restore_full_stock()
    {
        // 10 items dispatched, 40 remaining in main stock
        $this->item1->mainStocks()->first()->update(['remaining_quantity' => 40]);

        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        $transferItem = StockTransferItem::create([
            'transfer_id'   => $transfer->id,
            'item_id'       => $this->item1->id,
            'quantity'      => 10,
            'buying_price'  => 800,
            'selling_price' => 1000,
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('stock-transfers.delete-item', $transferItem));

        $response->assertRedirect();
        $this->assertDatabaseMissing('stock_transfer_items', ['id' => $transferItem->id]);
        $this->assertEquals(50, $this->item1->mainStocks()->first()->remaining_quantity);
    }

    public function test_owner_can_add_item_to_existing_transfer()
    {
        $transfer = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('stock-transfers.add-item', $transfer), [
                'item_id'  => $this->item2->id,
                'quantity' => 5,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $transfer->id,
            'item_id'     => $this->item2->id,
            'quantity'    => 5,
            'status'      => 'pending',
        ]);

        // Main stock for item2 should drop from 30 to 25
        $this->assertEquals(25, $this->item2->mainStocks()->first()->remaining_quantity);
    }
}
