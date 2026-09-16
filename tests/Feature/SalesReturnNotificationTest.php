<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $seller;
    protected Shop $shop;
    protected Item $item;
    protected Sale $sale;
    protected SaleItem $saleItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Create Shop
        $this->shop = Shop::create([
            'shop_name' => 'TestShop',
            'location'  => 'TestLocation',
        ]);

        // Create Users
        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner@test.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $this->seller = User::create([
            'name'     => 'Shop Seller',
            'email'    => 'seller@test.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
            'shop_id'  => $this->shop->id,
        ]);

        // Create Category & Item
        $category = Category::create(['category_name' => 'Laptops']);
        $this->item = Item::create([
            'item_name'       => 'HP EliteBook',
            'category_id'     => $category->id,
            'specification'   => 'Specs',
            'brand'           => 'HP',
            'model'           => 'EliteBook',
            'warranty_period' => '1 Year',
        ]);

        // Create Sale
        $this->sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->seller->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 5000,
        ]);

        $this->saleItem = SaleItem::create([
            'sale_id'           => $this->sale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 5,
            'selling_price'     => 1000,
            'owner_cost_price'  => 500,
            'owner_realized_sp' => 800,
            'shop_cost_price'   => 800,
            'shop_realized_sp'  => 1000,
        ]);
    }

    public function test_seller_return_creates_admin_notification()
    {
        $this->actingAs($this->seller);

        // Submit sales return request
        $response = $this->post(route('sales-returns.store', $this->sale), [
            'reason' => 'Defective screen',
            'items' => [
                [
                    'sale_item_id' => $this->saleItem->id,
                    'qty'          => 2,
                ]
            ]
        ]);

        $response->assertRedirect(route('sales-returns.index'));

        // Check SaleReturn status is pending
        $saleReturn = SaleReturn::latest()->first();
        $this->assertNotNull($saleReturn);
        $this->assertEquals('pending', $saleReturn->status);

        // Verify notification is created for the shop admin
        $notification = Notification::where('user_id', $this->admin->id)->latest()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('New Sales Return Request', $notification->title);
        $this->assertStringContainsString("Seller {$this->seller->name} has submitted a return request", $notification->message);

        // Verify seller doesn't get notified
        $sellerNotification = Notification::where('user_id', $this->seller->id)->first();
        $this->assertNull($sellerNotification);

        // Verify redirection URL works and points to index
        $this->actingAs($this->admin);
        $this->assertEquals(route('sales-returns.index'), $notification->destination_url);
    }

    public function test_admin_return_does_not_create_notification()
    {
        $this->actingAs($this->admin);

        // Submit return directly as admin (should auto-approve, no notifications)
        $response = $this->post(route('sales-returns.store', $this->sale), [
            'reason' => 'Customer changed mind',
            'items' => [
                [
                    'sale_item_id' => $this->saleItem->id,
                    'qty'          => 1,
                ]
            ]
        ]);

        $response->assertRedirect(route('sales-returns.index'));

        $saleReturn = SaleReturn::latest()->first();
        $this->assertNotNull($saleReturn);
        $this->assertEquals('approved', $saleReturn->status);

        // Verify no notification is created
        $this->assertEquals(0, Notification::count());
    }

    public function test_partial_return_keeps_unselected_items_on_sales_list()
    {
        $this->actingAs($this->admin);

        // Add a second item to the sale
        $item2 = Item::create([
            'item_name'       => 'VGA 1.5 M',
            'category_id'     => $this->item->category_id,
            'specification'   => 'Cable',
            'brand'           => 'Generic',
            'model'           => 'VGA',
            'warranty_period' => 'None',
        ]);

        $saleItem2 = SaleItem::create([
            'sale_id'           => $this->sale->id,
            'item_id'           => $item2->id,
            'quantity'          => 1,
            'selling_price'     => 5000,
            'owner_cost_price'  => 2000,
            'owner_realized_sp' => 5000,
            'shop_cost_price'   => 2000,
            'shop_realized_sp'  => 5000,
        ]);

        $this->sale->update(['total_amount' => 10000]); // 5000 + 5000

        // Return ONLY item2 (VGA 1.5 M)
        $response = $this->post(route('sales-returns.store', $this->sale), [
            'reason' => 'Customer only returning VGA cable',
            'items' => [
                [
                    'sale_item_id' => $saleItem2->id,
                    'qty'          => 1,
                ]
            ]
        ]);

        // Sale should NOT be soft-deleted
        $this->assertDatabaseHas('sales', [
            'id'         => $this->sale->id,
            'deleted_at' => null,
        ]);

        // Total amount should now be updated to 5000 (only item1 remaining)
        $this->sale->refresh();
        $this->assertEquals(5000, $this->sale->total_amount);

        // Unselected item (item1) should still exist on the sale
        $this->assertDatabaseHas('sale_items', [
            'id' => $this->saleItem->id,
        ]);

        // Selected item (item2) should be removed from the sale
        $this->assertDatabaseMissing('sale_items', [
            'id' => $saleItem2->id,
        ]);
    }

    public function test_repair_command_restores_partially_returned_soft_deleted_sales()
    {
        // Add item 2
        $item2 = Item::create([
            'item_name'       => 'VGA 1.5 M',
            'category_id'     => $this->item->category_id,
            'specification'   => 'Cable',
            'brand'           => 'Generic',
            'model'           => 'VGA',
            'warranty_period' => 'None',
        ]);

        $saleItem2 = SaleItem::create([
            'sale_id'           => $this->sale->id,
            'item_id'           => $item2->id,
            'quantity'          => 1,
            'selling_price'     => 5000,
            'owner_cost_price'  => 2000,
            'owner_realized_sp' => 5000,
            'shop_cost_price'   => 2000,
            'shop_realized_sp'  => 5000,
        ]);

        // Simulate old soft deletion & approved return
        $saleReturn = SaleReturn::create([
            'sale_id'      => $this->sale->id,
            'requested_by' => $this->admin->id,
            'approved_by'  => $this->admin->id,
            'status'       => 'approved',
            'reason'       => 'Old return',
            'return_date'  => now()->toDateString(),
        ]);

        \App\Models\SaleReturnItem::create([
            'sale_return_id' => $saleReturn->id,
            'item_id'        => $item2->id,
            'quantity'       => 1,
        ]);

        // Soft delete sale as was done under old logic
        $this->sale->delete();

        $this->assertSoftDeleted('sales', ['id' => $this->sale->id]);

        // Run repair command
        $this->artisan('sales:repair-partial-returns')
            ->expectsOutputToContain('Restored Sale #SL-' . $this->sale->id)
            ->assertExitCode(0);

        // Verify sale is restored and total updated
        $this->sale->refresh();
        $this->assertNull($this->sale->deleted_at);
        $this->assertEquals(5000, $this->sale->total_amount);
    }
}
