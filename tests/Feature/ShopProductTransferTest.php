<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\ShopProductTransfer;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $adminA;
    protected User $adminB;
    protected Shop $shopA;
    protected Shop $shopB;
    protected Item $wiredMouse;
    protected ShopStock $stockA;
    protected ShopStock $stockB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shopA = Shop::create(['shop_name' => 'Shop A', 'location' => 'Kariakoo', 'status' => 'active']);
        $this->shopB = Shop::create(['shop_name' => 'Shop B', 'location' => 'Posta', 'status' => 'active']);

        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner_pt@test.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->adminA = User::create([
            'name'     => 'Admin Shop A',
            'email'    => 'admin_a@test.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopA->id,
        ]);

        $this->adminB = User::create([
            'name'     => 'Admin Shop B',
            'email'    => 'admin_b@test.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopB->id,
        ]);

        $category = Category::create(['category_name' => 'Accessories']);
        $this->wiredMouse = Item::create([
            'item_name'   => 'Wired Mouse',
            'category_id' => $category->id,
        ]);

        // Shop A has 20 pc; Shop B has 1 pc
        $this->stockA = ShopStock::create([
            'shop_id'            => $this->shopA->id,
            'item_id'            => $this->wiredMouse->id,
            'quantity'           => 20,
            'remaining_quantity' => 20,
            'buying_price'       => 8000,
            'selling_price'      => 15000,
            'date_received'      => today(),
            'is_sellable'        => true,
            'is_admin_stock'     => false,
        ]);

        $this->stockB = ShopStock::create([
            'shop_id'            => $this->shopB->id,
            'item_id'            => $this->wiredMouse->id,
            'quantity'           => 1,
            'remaining_quantity' => 1,
            'buying_price'       => 8000,
            'selling_price'      => 15000,
            'date_received'      => today(),
            'is_sellable'        => true,
            'is_admin_stock'     => false,
        ]);
    }

    public function test_shop_b_requests_4_wired_mice_from_shop_a_and_confirmation_maintains_exact_stock_stability(): void
    {
        // 1. Shop B requests 4 Wired Mouse from Shop A
        $createRes = $this->actingAs($this->adminB)->post(route('product-transfers.store'), [
            'from_shop_id' => $this->shopA->id,
            'items'        => [
                ['id' => $this->wiredMouse->id, 'qty' => 4],
            ],
            'notes' => 'Urgent customer demand at Shop B',
        ]);

        $transfer = ShopProductTransfer::latest('id')->first();
        $this->assertNotNull($transfer);
        $createRes->assertRedirect(route('product-transfers.show', $transfer));
        $this->assertEquals('pending', $transfer->status);

        // Stock must NOT move while still pending
        $this->stockA->refresh();
        $this->stockB->refresh();
        $this->assertEquals(20, (int) $this->stockA->remaining_quantity);
        $this->assertEquals(1, (int) $this->stockB->remaining_quantity);

        // Shop A admin notified of incoming request
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminA->id,
            'title'   => 'Incoming Product Transfer Request',
        ]);

        // Shop B cannot confirm its own request
        $this->actingAs($this->adminB)
            ->post(route('product-transfers.confirm', $transfer))
            ->assertStatus(403);

        // 2. Shop A admin confirms transfer
        $confirmRes = $this->actingAs($this->adminA)->post(route('product-transfers.confirm', $transfer));
        $confirmRes->assertRedirect(route('product-transfers.show', $transfer));

        $transfer->refresh();
        $this->stockA->refresh();
        $this->stockB->refresh();

        $this->assertEquals('confirmed', $transfer->status);
        // Shop A: 20 -> 16 pc (both quantity & remaining_quantity adjusted so 0 phantom sales!)
        $this->assertEquals(16, (int) $this->stockA->remaining_quantity);
        $this->assertEquals(16, (int) $this->stockA->quantity);
        // Shop B: 1 -> 5 pc
        $this->assertEquals(5, (int) $this->stockB->remaining_quantity);
        $this->assertEquals(5, (int) $this->stockB->quantity);

        // Total stock across both shops remains conserved: 16 + 5 = 21 pc
        $totalAcrossShops = (int) ShopStock::where('item_id', $this->wiredMouse->id)->sum('remaining_quantity');
        $this->assertEquals(21, $totalAcrossShops);

        // Audit StockLog recorded
        $this->assertDatabaseHas('stock_logs', [
            'item_id'          => $this->wiredMouse->id,
            'from_location'    => 'Shop A',
            'to_location'      => 'Shop B',
            'quantity'         => 4,
            'transaction_type' => 'STOCK_TRANSFER',
        ]);

        // 3. Owner has full read-only transparency (sees transfer details, qty, amount, shops & status,
        //    receives notifications, and cannot mutate/revert branch transfers)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Inter-Shop Product Transfer Requested',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Inter-Shop Product Transfer Completed',
        ]);

        $ownerView = $this->actingAs($this->owner)->get(route('product-transfers.show', $transfer));
        $ownerView->assertStatus(200)
            ->assertSee('Wired Mouse')
            ->assertSee('Shop A')
            ->assertSee('Shop B')
            ->assertSee('60,000')
            ->assertSee('Owner Transparency View');

        $this->actingAs($this->owner)->post(route('product-transfers.revert', $transfer), [
            'revert_reason' => 'Not allowed in read-only mode',
        ])->assertStatus(403);
    }

    public function test_confirmation_fails_safely_if_shop_a_stock_drops_below_requested_qty(): void
    {
        $this->actingAs($this->adminB)->post(route('product-transfers.store'), [
            'from_shop_id' => $this->shopA->id,
            'items'        => [
                ['id' => $this->wiredMouse->id, 'qty' => 5],
            ],
        ]);

        $transfer = ShopProductTransfer::latest('id')->firstOrFail();

        // Simulate Shop A selling 18 units while transfer is pending (only 2 left)
        $this->stockA->update(['remaining_quantity' => 2]);

        $res = $this->actingAs($this->adminA)->post(route('product-transfers.confirm', $transfer));
        $res->assertSessionHas('error');

        $transfer->refresh();
        $this->stockA->refresh();
        $this->stockB->refresh();

        $this->assertEquals('pending', $transfer->status);
        $this->assertEquals(2, (int) $this->stockA->remaining_quantity);
        $this->assertEquals(1, (int) $this->stockB->remaining_quantity);
    }

    public function test_rejected_request_leaves_stock_untouched(): void
    {
        $this->actingAs($this->adminB)->post(route('product-transfers.store'), [
            'from_shop_id' => $this->shopA->id,
            'items'        => [
                ['id' => $this->wiredMouse->id, 'qty' => 4],
            ],
        ]);

        $transfer = ShopProductTransfer::latest('id')->firstOrFail();

        $this->actingAs($this->adminA)->post(route('product-transfers.reject', $transfer), [
            'rejection_reason' => 'Reserved for corporate order',
        ])->assertRedirect(route('product-transfers.show', $transfer));

        $transfer->refresh();
        $this->stockA->refresh();
        $this->stockB->refresh();

        $this->assertEquals('rejected', $transfer->status);
        $this->assertEquals(20, (int) $this->stockA->remaining_quantity);
        $this->assertEquals(1, (int) $this->stockB->remaining_quantity);
    }

    public function test_supplying_shop_receives_in_app_notification_and_pending_reminder_when_request_arrives(): void
    {
        // Shop B submits a product transfer request to Shop A
        $this->actingAs($this->adminB)->post(route('product-transfers.store'), [
            'from_shop_id' => $this->shopA->id,
            'items'        => [
                ['id' => $this->wiredMouse->id, 'qty' => 3],
            ],
            'notes' => 'Customer waiting at Posta branch',
        ])->assertRedirect();

        $transfer = ShopProductTransfer::latest('id')->firstOrFail();

        // 1. Bell notification exists for Shop A admin and deep-links straight to the transfer show page
        $notif = Notification::where('user_id', $this->adminA->id)
            ->where('title', 'Incoming Product Transfer Request')
            ->latest('id')
            ->first();

        $this->assertNotNull($notif);
        $this->actingAs($this->adminA);
        $this->assertEquals(route('product-transfers.show', $transfer), $notif->destination_url);

        // 2. Real-time in-app reminder endpoint returns the incoming request for Shop A admin
        $reminderRes = $this->actingAs($this->adminA)
            ->getJson(route('product-transfers.pending-reminders'))
            ->assertOk()
            ->assertJsonPath('total_pending', 1)
            ->assertJsonPath('due_transfers.0.id', $transfer->id)
            ->assertJsonPath('due_transfers.0.transfer_number', $transfer->transfer_number)
            ->assertJsonPath('due_transfers.0.requesting_shop', 'Shop B')
            ->assertJsonPath('due_transfers.0.total_qty', 3);

        $this->assertEquals(route('product-transfers.show', $transfer), $reminderRes->json('due_transfers.0.url'));

        // 3. Requesting shop (Shop B) and Owner do NOT get Shop A's incoming action-required popup
        $this->actingAs($this->adminB)
            ->getJson(route('product-transfers.pending-reminders'))
            ->assertOk()
            ->assertJsonPath('total_pending', 0)
            ->assertJsonCount(0, 'due_transfers');

        $this->actingAs($this->owner)
            ->getJson(route('product-transfers.pending-reminders'))
            ->assertOk()
            ->assertJsonPath('total_pending', 0)
            ->assertJsonCount(0, 'due_transfers');

        // 4. Snoozing dismisses that specific request while leaving total_pending accurate
        $this->actingAs($this->adminA)
            ->postJson(route('product-transfers.pending-reminders.dismiss'), [
                'transfer_ids' => [$transfer->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($this->adminA)
            ->getJson(route('product-transfers.pending-reminders'))
            ->assertOk()
            ->assertJsonPath('total_pending', 1)
            ->assertJsonCount(0, 'due_transfers');

        // 5. Once Shop A confirms the request, total_pending drops to 0
        $this->actingAs($this->adminA)
            ->post(route('product-transfers.confirm', $transfer))
            ->assertRedirect();

        $this->actingAs($this->adminA)
            ->getJson(route('product-transfers.pending-reminders'))
            ->assertOk()
            ->assertJsonPath('total_pending', 0)
            ->assertJsonCount(0, 'due_transfers');
    }
}
