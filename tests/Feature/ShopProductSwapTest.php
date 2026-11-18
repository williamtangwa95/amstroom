<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopProductSwap;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductSwapTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $adminA;
    protected User $adminB;
    protected Shop $shopA;
    protected Shop $shopB;
    protected Unit $unit;
    protected Category $category;
    protected Item $itemA; // Lenovo 11e
    protected Item $itemB; // Dell Latitude 3310

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shopA = Shop::create([
            'shop_name' => 'City Branch (Shop A)',
            'location'  => 'City Center',
            'status'    => 'active',
        ]);

        $this->shopB = Shop::create([
            'shop_name' => 'Kariakoo Branch (Shop B)',
            'location'  => 'Kariakoo',
            'status'    => 'active',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->adminA = User::create([
            'name'     => 'Admin Shop A',
            'email'    => 'adminA@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopA->id,
        ]);

        $this->adminB = User::create([
            'name'     => 'Admin Shop B',
            'email'    => 'adminB@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopB->id,
        ]);

        $this->unit = Unit::create([
            'name'   => 'Piece',
            'symbol' => 'pc',
        ]);

        $this->category = Category::create([
            'category_name' => 'Laptops',
        ]);

        // Item A: Lenovo 11e
        $this->itemA = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Lenovo ThinkPad 11e',
            'brand'       => 'Lenovo',
        ]);

        // Item B: Dell Latitude 3310
        $this->itemB = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell Latitude 3310',
            'brand'       => 'Dell',
        ]);

        // Shop A has 5 units of Lenovo 11e
        ShopStock::create([
            'shop_id'            => $this->shopA->id,
            'item_id'            => $this->itemA->id,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'buying_price'       => 350000,
            'selling_price'      => 450000,
        ]);

        // Shop B has 3 units of Dell Latitude 3310
        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $this->itemB->id,
            'quantity'           => 3,
            'remaining_quantity' => 3,
            'buying_price'       => 400000,
            'selling_price'      => 520000,
        ]);
    }

    public function test_shop_a_can_propose_product_swap_to_shop_b()
    {
        $payload = [
            'source_shop_id'       => $this->shopA->id,
            'destination_shop_id'  => $this->shopB->id,
            'source_item_id'       => $this->itemA->id,
            'source_quantity'      => 1,
            'destination_item_id'  => $this->itemB->id,
            'destination_quantity' => 1,
            'reason_type'          => 'customer_preference',
            'notes'                => 'Customer at Shop A specifically requested Dell Latitude 3310',
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('shop-swaps.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('shop_product_swaps', [
            'source_shop_id'       => $this->shopA->id,
            'destination_shop_id'  => $this->shopB->id,
            'source_item_id'       => $this->itemA->id,
            'source_quantity'      => 1,
            'destination_item_id'  => $this->itemB->id,
            'destination_quantity' => 1,
            'status'               => 'pending_destination',
            'initiated_by'         => $this->adminA->id,
        ]);
    }

    public function test_destination_shop_admin_can_approve_swap()
    {
        $swap = ShopProductSwap::create([
            'source_shop_id'       => $this->shopA->id,
            'destination_shop_id'  => $this->shopB->id,
            'source_item_id'       => $this->itemA->id,
            'source_quantity'      => 1,
            'destination_item_id'  => $this->itemB->id,
            'destination_quantity' => 1,
            'reason_type'          => 'customer_preference',
            'status'               => 'pending_destination',
            'initiated_by'         => $this->adminA->id,
        ]);

        $response = $this->actingAs($this->adminB)
            ->post(route('shop-swaps.approve-destination', $swap));

        $response->assertRedirect();

        $swap->refresh();
        $this->assertEquals('pending_owner', $swap->status);
        $this->assertEquals($this->adminB->id, $swap->destination_approved_by);
        $this->assertNotNull($swap->destination_approved_at);
    }

    public function test_owner_confirmation_atomically_exchanges_stocks_between_shops()
    {
        $swap = ShopProductSwap::create([
            'source_shop_id'          => $this->shopA->id,
            'destination_shop_id'     => $this->shopB->id,
            'source_item_id'          => $this->itemA->id,
            'source_quantity'         => 1,
            'destination_item_id'     => $this->itemB->id,
            'destination_quantity'    => 1,
            'reason_type'             => 'customer_preference',
            'status'                  => 'pending_owner',
            'initiated_by'            => $this->adminA->id,
            'destination_approved_by' => $this->adminB->id,
            'destination_approved_at' => now(),
        ]);

        // Prior to swap:
        // Shop A has: Item A (5), Item B (0)
        // Shop B has: Item B (3), Item A (0)
        $this->assertEquals(5, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
        $this->assertNull(ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemB->id)->first());

        $this->assertEquals(3, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));
        $this->assertNull(ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemA->id)->first());

        $response = $this->actingAs($this->owner)
            ->post(route('shop-swaps.confirm-owner', $swap));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $swap->refresh();
        $this->assertEquals('confirmed', $swap->status);
        $this->assertEquals($this->owner->id, $swap->owner_approved_by);
        $this->assertNotNull($swap->owner_approved_at);

        // After swap confirmed:
        // Shop A: Item A decremented from 5 to 4; Item B adopted (+1)
        $this->assertEquals(4, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
        $this->assertEquals(1, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));

        // Shop B: Item B decremented from 3 to 2; Item A adopted (+1)
        $this->assertEquals(2, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));
        $this->assertEquals(1, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));

        // Verify audit logs
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->itemA->id,
            'transaction_type' => 'STOCK_TRANSFER',
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->itemB->id,
            'transaction_type' => 'STOCK_TRANSFER',
        ]);
    }

    public function test_either_party_or_owner_can_cancel_swap()
    {
        $swap = ShopProductSwap::create([
            'source_shop_id'       => $this->shopA->id,
            'destination_shop_id'  => $this->shopB->id,
            'source_item_id'       => $this->itemA->id,
            'source_quantity'      => 1,
            'destination_item_id'  => $this->itemB->id,
            'destination_quantity' => 1,
            'reason_type'          => 'customer_preference',
            'status'               => 'pending_destination',
            'initiated_by'         => $this->adminA->id,
        ]);

        $response = $this->actingAs($this->adminB)
            ->post(route('shop-swaps.cancel', $swap), [
                'cancellation_reason' => 'Item already promised to a walk-in customer.',
            ]);

        $response->assertRedirect();

        $swap->refresh();
        $this->assertEquals('cancelled', $swap->status);
        $this->assertEquals($this->adminB->id, $swap->cancelled_by);
        $this->assertEquals('Item already promised to a walk-in customer.', $swap->cancellation_reason);
    }

    public function test_owner_can_revert_confirmed_swap_and_restore_original_stocks()
    {
        $swap = ShopProductSwap::create([
            'source_shop_id'          => $this->shopA->id,
            'destination_shop_id'     => $this->shopB->id,
            'source_item_id'          => $this->itemA->id,
            'source_quantity'         => 1,
            'destination_item_id'     => $this->itemB->id,
            'destination_quantity'    => 1,
            'reason_type'             => 'customer_preference',
            'status'                  => 'pending_owner',
            'initiated_by'            => $this->adminA->id,
            'destination_approved_by' => $this->adminB->id,
            'destination_approved_at' => now(),
        ]);

        // First confirm
        $this->actingAs($this->owner)->post(route('shop-swaps.confirm-owner', $swap));

        // Now revert
        $response = $this->actingAs($this->owner)
            ->post(route('shop-swaps.revert', $swap), [
                'revert_reason' => 'Customer cancelled order and returned item.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $swap->refresh();
        $this->assertEquals('reverted', $swap->status);
        $this->assertEquals($this->owner->id, $swap->reverted_by);
        $this->assertEquals('Customer cancelled order and returned item.', $swap->revert_reason);

        // Original stocks must be fully restored:
        // Shop A: Item A returns to 5; Item B returns to 0
        $this->assertEquals(5, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
        $this->assertEquals(0, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));

        // Shop B: Item B returns to 3; Item A returns to 0
        $this->assertEquals(3, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));
        $this->assertEquals(0, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
    }
}
