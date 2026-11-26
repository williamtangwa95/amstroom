<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Notification;
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

        $this->unit = Unit::firstOrCreate([
            'name' => 'Piece',
        ], [
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

    public function test_shop_a_can_propose_product_swap_to_shop_b_and_owner_cannot_see_pending_swap()
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

        $swap = ShopProductSwap::firstOrFail();

        // Destination Shop Admin is notified; Owner is NOT notified yet
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminB->id,
            'title'   => 'Incoming Product Swap Request',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->owner->id,
        ]);

        // Owner does NOT see pending swaps in index and cannot view pending swap detail
        $this->actingAs($this->owner)
            ->get(route('shop-swaps.index'))
            ->assertOk()
            ->assertDontSee($swap->swap_number);

        $this->actingAs($this->owner)
            ->get(route('shop-swaps.show', $swap))
            ->assertForbidden();
    }

    public function test_owner_cannot_initiate_or_approve_product_swap()
    {
        $this->actingAs($this->owner)
            ->get(route('shop-swaps.create'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post(route('shop-swaps.store'), [
                'source_shop_id'       => $this->shopA->id,
                'destination_shop_id'  => $this->shopB->id,
                'source_item_id'       => $this->itemA->id,
                'source_quantity'      => 1,
                'destination_item_id'  => $this->itemB->id,
                'destination_quantity' => 1,
                'reason_type'          => 'customer_preference',
            ])
            ->assertForbidden();

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

        $this->actingAs($this->owner)
            ->post(route('shop-swaps.approve-destination', $swap))
            ->assertForbidden();
    }

    public function test_destination_shop_admin_approval_atomically_confirms_swap_and_shows_to_owner()
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

        // Prior to swap:
        // Shop A has: Item A (5), Item B (0)
        // Shop B has: Item B (3), Item A (0)
        $this->assertEquals(5, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
        $this->assertNull(ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemB->id)->first());

        $this->assertEquals(3, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));
        $this->assertNull(ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemA->id)->first());

        // Destination Shop Admin approves & immediately confirms the swap
        $response = $this->actingAs($this->adminB)
            ->post(route('shop-swaps.approve-destination', $swap));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $swap->refresh();
        $this->assertEquals('confirmed', $swap->status);
        $this->assertEquals($this->adminB->id, $swap->destination_approved_by);
        $this->assertNotNull($swap->destination_approved_at);
        $this->assertNull($swap->owner_approved_by);

        // After swap confirmed:
        // Shop A: Item A decremented from 5 to 4; Item B adopted (+1)
        $this->assertEquals(4, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));
        $this->assertEquals(1, ShopStock::where('shop_id', $this->shopA->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));

        // Shop B: Item B decremented from 3 to 2; Item A adopted (+1)
        $this->assertEquals(2, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemB->id)->value('remaining_quantity'));
        $this->assertEquals(1, ShopStock::where('shop_id', $this->shopB->id)->where('item_id', $this->itemA->id)->value('remaining_quantity'));

        // Verify audit logs performed by Destination Shop Admin
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->itemA->id,
            'transaction_type' => 'STOCK_TRANSFER',
            'performed_by'     => $this->adminB->id,
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->itemB->id,
            'transaction_type' => 'STOCK_TRANSFER',
            'performed_by'     => $this->adminB->id,
        ]);

        // Owner is notified once swap is done and can now see the completed swap
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Product Swap Completed',
        ]);

        $this->actingAs($this->owner)
            ->get(route('shop-swaps.index'))
            ->assertOk()
            ->assertSee($swap->swap_number);

        $this->actingAs($this->owner)
            ->get(route('shop-swaps.show', $swap))
            ->assertOk()
            ->assertSee($swap->swap_number);
    }

    public function test_either_shop_admin_can_cancel_pending_swap()
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

        // First Destination Shop Admin approves & confirms
        $this->actingAs($this->adminB)->post(route('shop-swaps.approve-destination', $swap));

        // Now Owner reverts
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

    public function test_destination_items_only_allow_same_category_or_same_product_and_exclude_other_categories()
    {
        $accessoryCategory = Category::create(['category_name' => 'Chargers']);

        $touchLaptop = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell Latitude 3190 2-in-1 Touchscreen Laptop',
            'brand'       => 'Dell',
        ]);

        $nonTouchLaptop = Item::create([
            'category_id' => $this->category->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell Latitude 3190 2-in-1 Non-Touchscreen Laptop',
            'brand'       => 'Dell',
        ]);

        $charger = Item::create([
            'category_id' => $accessoryCategory->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell 65W Laptop Charger',
            'brand'       => 'Dell',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $touchLaptop->id,
            'quantity'           => 2,
            'remaining_quantity' => 2,
            'buying_price'       => 420000,
            'selling_price'      => 550000,
        ]);

        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $nonTouchLaptop->id,
            'quantity'           => 2,
            'remaining_quantity' => 2,
            'buying_price'       => 380000,
            'selling_price'      => 500000,
        ]);

        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $charger->id,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'buying_price'       => 25000,
            'selling_price'      => 45000,
        ]);

        // "all" filter is rejected
        $this->actingAs($this->adminA)
            ->getJson(route('shop-swaps.destination-items', [
                'destination_shop_id' => $this->shopB->id,
                'source_item_id'      => $touchLaptop->id,
                'filter_type'         => 'all',
            ]))
            ->assertStatus(422);

        // same_category returns laptops (including non-touchscreen variant) and excludes chargers
        $sameCatResponse = $this->actingAs($this->adminA)
            ->getJson(route('shop-swaps.destination-items', [
                'destination_shop_id' => $this->shopB->id,
                'source_item_id'      => $touchLaptop->id,
                'filter_type'         => 'same_category',
            ]))
            ->assertOk();

        $sameCatIds = collect($sameCatResponse->json('items'))->pluck('item_id')->all();
        $this->assertContains($nonTouchLaptop->id, $sameCatIds);
        $this->assertContains($touchLaptop->id, $sameCatIds);
        $this->assertNotContains($charger->id, $sameCatIds);

        // same_product returns only the exact same product
        $sameProdResponse = $this->actingAs($this->adminA)
            ->getJson(route('shop-swaps.destination-items', [
                'destination_shop_id' => $this->shopB->id,
                'source_item_id'      => $touchLaptop->id,
                'filter_type'         => 'same_product',
            ]))
            ->assertOk();

        $sameProdIds = collect($sameProdResponse->json('items'))->pluck('item_id')->all();
        $this->assertSame([$touchLaptop->id], $sameProdIds);
    }

    public function test_cannot_swap_products_from_different_categories()
    {
        $accessoryCategory = Category::create(['category_name' => 'Chargers']);

        $charger = Item::create([
            'category_id' => $accessoryCategory->id,
            'unit_id'     => $this->unit->id,
            'item_name'   => 'Dell 65W Laptop Charger',
            'brand'       => 'Dell',
        ]);

        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $charger->id,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'buying_price'       => 25000,
            'selling_price'      => 45000,
        ]);

        $response = $this->actingAs($this->adminA)
            ->post(route('shop-swaps.store'), [
                'destination_shop_id'  => $this->shopB->id,
                'source_item_id'       => $this->itemA->id,
                'source_quantity'      => 1,
                'destination_item_id'  => $charger->id,
                'destination_quantity' => 1,
                'swap_mode'            => 'same_category',
                'reason_type'          => 'customer_preference',
            ]);

        $response->assertSessionHasErrors('destination_item_id');
        $this->assertDatabaseCount('shop_product_swaps', 0);
    }

    public function test_same_product_mode_requires_exact_same_product_while_same_category_allows_same_category_variants()
    {
        // Give Shop B stock of Item A as well
        ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $this->itemA->id,
            'quantity'           => 2,
            'remaining_quantity' => 2,
            'buying_price'       => 350000,
            'selling_price'      => 450000,
        ]);

        // same_product mode rejects swapping Item A for Item B even though both are Laptops
        $this->actingAs($this->adminA)
            ->post(route('shop-swaps.store'), [
                'destination_shop_id'  => $this->shopB->id,
                'source_item_id'       => $this->itemA->id,
                'source_quantity'      => 1,
                'destination_item_id'  => $this->itemB->id,
                'destination_quantity' => 1,
                'swap_mode'            => 'same_product',
                'reason_type'          => 'defective_replacement',
            ])
            ->assertSessionHasErrors('destination_item_id');

        $this->assertDatabaseCount('shop_product_swaps', 0);

        // same_product mode accepts swapping Item A for Item A
        $this->actingAs($this->adminA)
            ->post(route('shop-swaps.store'), [
                'destination_shop_id'  => $this->shopB->id,
                'source_item_id'       => $this->itemA->id,
                'source_quantity'      => 1,
                'destination_item_id'  => $this->itemA->id,
                'destination_quantity' => 1,
                'swap_mode'            => 'same_product',
                'reason_type'          => 'defective_replacement',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shop_product_swaps', [
            'source_item_id'      => $this->itemA->id,
            'destination_item_id' => $this->itemA->id,
            'status'              => 'pending_destination',
        ]);
    }

    public function test_cannot_swap_unequal_quantities()
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('shop-swaps.store'), [
                'destination_shop_id'  => $this->shopB->id,
                'source_item_id'       => $this->itemA->id,
                'source_quantity'      => 2,
                'destination_item_id'  => $this->itemB->id,
                'destination_quantity' => 1,
                'swap_mode'            => 'same_category',
                'reason_type'          => 'customer_preference',
            ]);

        $response->assertSessionHasErrors('destination_quantity');
        $this->assertDatabaseCount('shop_product_swaps', 0);
    }
}
