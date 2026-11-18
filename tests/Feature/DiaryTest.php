<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaryTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shopA;
    protected Shop $shopB;
    protected User $owner;
    protected User $adminA;
    protected User $sellerA;
    protected User $adminB;
    protected User $sellerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shopA = Shop::create(['shop_name' => 'Shop Alpha', 'location' => 'Dar es Salaam']);
        $this->shopB = Shop::create(['shop_name' => 'Shop Beta', 'location' => 'Arusha']);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'owner',
        ]);

        $this->adminA = User::create([
            'name'     => 'Admin Alpha',
            'email'    => 'admina@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopA->id,
        ]);

        $this->sellerA = User::create([
            'name'     => 'Seller Alpha',
            'email'    => 'sellera@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'seller',
            'shop_id'  => $this->shopA->id,
        ]);

        $this->adminB = User::create([
            'name'     => 'Admin Beta',
            'email'    => 'adminb@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shopB->id,
        ]);

        $this->sellerB = User::create([
            'name'     => 'Seller Beta',
            'email'    => 'sellerb@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'seller',
            'shop_id'  => $this->shopB->id,
        ]);
    }

    public function test_mandatory_fields_validation(): void
    {
        $response = $this->actingAs($this->sellerA)->post(route('diary.store'), [
            'type' => 'personal',
        ]);

        $response->assertSessionHasErrors(['event_name', 'event_description']);
    }

    public function test_seller_can_create_personal_diary_and_it_is_private(): void
    {
        $response = $this->actingAs($this->sellerA)->post(route('diary.store'), [
            'type'              => 'personal',
            'event_name'        => 'Seller Private Notes',
            'event_description' => 'Personal task details',
            'event_date'        => now()->toDateString(),
            'cost'              => 5000,
            'needs_follow_up'   => '1',
        ]);

        $response->assertRedirect(route('diary.index'));
        $this->assertDatabaseHas('diaries', [
            'user_id'         => $this->sellerA->id,
            'type'            => 'personal',
            'event_name'      => 'Seller Private Notes',
            'needs_follow_up' => true,
        ]);

        // Seller A sees it in their data
        $dataResA = $this->actingAs($this->sellerA)->getJson(route('diary.data'));
        $dataResA->assertOk();
        $this->assertCount(1, $dataResA->json('data'));

        // Admin A in same shop cannot see Seller A's personal diary
        $dataResAdminA = $this->actingAs($this->adminA)->getJson(route('diary.data'));
        $dataResAdminA->assertOk();
        $this->assertCount(0, $dataResAdminA->json('data'));

        // Owner cannot see Seller A's personal diary
        $dataResOwner = $this->actingAs($this->owner)->getJson(route('diary.data'));
        $dataResOwner->assertOk();
        $this->assertCount(0, $dataResOwner->json('data'));
    }

    public function test_shop_diary_is_accessible_by_shop_admin_and_seller_of_that_shop(): void
    {
        // Seller A creates a shop diary entry for Shop A
        $response = $this->actingAs($this->sellerA)->post(route('diary.store'), [
            'type'              => 'shop',
            'event_name'        => 'Daily Register Reconciliation',
            'event_description' => 'Counted register cash and reconciled POS reports.',
            'event_date'        => now()->toDateString(),
            'needs_follow_up'   => '0',
        ]);

        $response->assertRedirect(route('diary.index'));
        $this->assertDatabaseHas('diaries', [
            'user_id'    => $this->sellerA->id,
            'shop_id'    => $this->shopA->id,
            'type'       => 'shop',
            'event_name' => 'Daily Register Reconciliation',
        ]);

        // Seller A sees it
        $resSellerA = $this->actingAs($this->sellerA)->getJson(route('diary.data'));
        $resSellerA->assertOk();
        $this->assertCount(1, $resSellerA->json('data'));

        // Admin A of Shop A can see it!
        $resAdminA = $this->actingAs($this->adminA)->getJson(route('diary.data'));
        $resAdminA->assertOk();
        $this->assertCount(1, $resAdminA->json('data'));

        // Admin B & Seller B of Shop B cannot see Shop A's diary
        $resAdminB = $this->actingAs($this->adminB)->getJson(route('diary.data'));
        $resAdminB->assertOk();
        $this->assertCount(0, $resAdminB->json('data'));

        $resSellerB = $this->actingAs($this->sellerB)->getJson(route('diary.data'));
        $resSellerB->assertOk();
        $this->assertCount(0, $resSellerB->json('data'));

        // Owner only has personal diary, so shop diary entries are not shown in owner's diary
        $resOwner = $this->actingAs($this->owner)->getJson(route('diary.data'));
        $resOwner->assertOk();
        $this->assertCount(0, $resOwner->json('data'));
    }

    public function test_owner_can_only_create_personal_diary(): void
    {
        // Even if type is omitted or set, Owner is strictly restricted to personal diary
        $response = $this->actingAs($this->owner)->post(route('diary.store'), [
            'event_name'        => 'Executive Review with Partners',
            'event_description' => 'Reviewed quarterly growth targets.',
            'event_date'        => now()->toDateString(),
            'cost'              => 50000,
        ]);

        $response->assertRedirect(route('diary.index'));
        $this->assertDatabaseHas('diaries', [
            'user_id'    => $this->owner->id,
            'type'       => 'personal',
            'shop_id'    => null,
            'event_name' => 'Executive Review with Partners',
        ]);

        // Owner sees their personal diary
        $resOwner = $this->actingAs($this->owner)->getJson(route('diary.data'));
        $this->assertCount(1, $resOwner->json('data'));

        // Shop admin and sellers cannot see Owner's personal diary
        $resAdmin = $this->actingAs($this->adminA)->getJson(route('diary.data'));
        $this->assertCount(0, $resAdmin->json('data'));
    }

    public function test_shop_admin_can_edit_and_delete_shop_entry(): void
    {
        $entry = Diary::create([
            'user_id'           => $this->sellerA->id,
            'shop_id'           => $this->shopA->id,
            'type'              => 'shop',
            'event_name'        => 'Old Event Name',
            'event_description' => 'Old description',
            'needs_follow_up'   => false,
        ]);

        // Admin A updates Seller A's shop entry
        $updateRes = $this->actingAs($this->adminA)->put(route('diary.update', $entry->id), [
            'type'              => 'shop',
            'event_name'        => 'Updated Event by Admin',
            'event_description' => 'Updated description',
            'needs_follow_up'   => '1',
        ]);

        $updateRes->assertRedirect(route('diary.index'));
        $this->assertDatabaseHas('diaries', [
            'id'              => $entry->id,
            'event_name'      => 'Updated Event by Admin',
            'needs_follow_up' => true,
        ]);

        // Admin A deletes the entry
        $deleteRes = $this->actingAs($this->adminA)->delete(route('diary.destroy', $entry->id));
        $deleteRes->assertRedirect(route('diary.index'));
        $this->assertDatabaseMissing('diaries', ['id' => $entry->id]);
    }

    public function test_unauthorized_user_cannot_delete_other_users_entry(): void
    {
        $entry = Diary::create([
            'user_id'           => $this->sellerA->id,
            'type'              => 'personal',
            'event_name'        => 'Seller A Secret',
            'event_description' => 'Private',
        ]);

        // Seller B attempts to delete Seller A's entry
        $response = $this->actingAs($this->sellerB)->delete(route('diary.destroy', $entry->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('diaries', ['id' => $entry->id]);
    }
}
