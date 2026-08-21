<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDisablingTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->seller = User::create([
            'name'     => 'Seller User',
            'email'    => 'seller@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
        ]);

        $this->owner->refresh();
        $this->seller->refresh();
    }

    public function test_owner_can_toggle_user_status()
    {
        $this->actingAs($this->owner);

        // Initially active
        $this->assertEquals('active', $this->seller->status);

        // Toggle to inactive
        $response = $this->patch(route('users.toggle-status', $this->seller));
        $response->assertRedirect();
        
        $this->seller->refresh();
        $this->assertEquals('inactive', $this->seller->status);

        // Toggle back to active
        $response = $this->patch(route('users.toggle-status', $this->seller));
        $response->assertRedirect();

        $this->seller->refresh();
        $this->assertEquals('active', $this->seller->status);
    }

    public function test_inactive_user_cannot_login()
    {
        $this->seller->update(['status' => 'inactive']);

        $response = $this->post(route('login.post'), [
            'email' => $this->seller->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(auth()->check());
    }

    public function test_active_user_can_login()
    {
        $response = $this->post(route('login.post'), [
            'email' => $this->seller->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(auth()->check());
    }

    public function test_inactive_user_cannot_request_password_reset()
    {
        $this->seller->update(['status' => 'inactive']);

        $response = $this->post(route('password.email'), [
            'email' => $this->seller->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_middleware_logs_out_inactive_user_on_request()
    {
        $this->actingAs($this->seller);

        // User is active, gets dashboard fine
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Make user inactive
        $this->seller->update(['status' => 'inactive']);

        // Next request should redirect to login and log out
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertFalse(auth()->check());
    }

    public function test_cannot_delete_user_with_dependencies()
    {
        $this->actingAs($this->owner);

        // Verify we can delete them initially (no dependencies)
        $seller2 = User::create([
            'name'     => 'Seller 2',
            'email'    => 'seller2@example.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
        ]);
        $seller2->refresh();

        $this->assertFalse($seller2->hasDependencies());

        // Create a shop and sale to establish dependency
        $shop = \App\Models\Shop::create([
            'shop_name' => 'Test Shop',
            'location'  => 'Test Location',
        ]);

        \App\Models\Sale::create([
            'shop_id'       => $shop->id,
            'seller_id'     => $seller2->id,
            'sale_date'     => now(),
            'total_amount'  => 100.00,
        ]);

        $this->assertTrue($seller2->hasDependencies());

        // Try deleting via endpoint
        $response = $this->delete(route('users.destroy', $seller2));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('users', ['id' => $seller2->id]);
    }
}
