<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected User $seller;

    /**
     * Set up tests, including disabling CSRF for testing convenience.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Create representative users
        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
        ]);

        $this->seller = User::create([
            'name'     => 'Alice Seller',
            'email'    => 'seller_test@amstroom.com',
            'password' => bcrypt('password'),
            'role'     => 'seller',
        ]);
    }

    public function test_middleware_records_visitor_requests()
    {
        $this->actingAs($this->seller);

        // Access dashboard
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        // Assert record is created
        $this->assertDatabaseHas('visitor_logs', [
            'url'     => '/dashboard',
            'method'  => 'GET',
            'user_id' => $this->seller->id,
        ]);
    }

    public function test_middleware_ignores_polling_and_ajax_requests()
    {
        $this->actingAs($this->owner);

        // Access poll route
        $response = $this->get(route('notifications.poll'));

        // Assert it was NOT logged in visitor_logs
        $this->assertDatabaseMissing('visitor_logs', [
            'url' => '/notifications/poll',
        ]);
    }

    public function test_visitor_analytics_page_is_restricted_to_owner_and_admin()
    {
        // Seller should get 403 Forbidden
        $this->actingAs($this->seller);
        $response = $this->get(route('reports.visitors'));
        $response->assertStatus(403);

        // Admin should get 200 OK
        $this->actingAs($this->admin);
        $response = $this->get(route('reports.visitors'));
        $response->assertStatus(200);

        // Owner should get 200 OK
        $this->actingAs($this->owner);
        $response = $this->get(route('reports.visitors'));
        $response->assertStatus(200);
    }

    public function test_visitor_analytics_page_displays_correct_metrics()
    {
        $this->actingAs($this->owner);

        // Create test logs - 2 Desktops, 1 Mobile to break device tie
        VisitorLog::create([
            'ip_address'  => '45.221.196.65',
            'url'         => '/dashboard',
            'method'      => 'GET',
            'user_id'     => $this->owner->id,
            'device_type' => 'Desktop',
            'browser'     => 'Chrome',
            'platform'    => 'Windows',
            'city'        => 'Morogoro',
            'country'     => 'Tanzania',
        ]);

        VisitorLog::create([
            'ip_address'  => '45.221.196.66',
            'url'         => '/items',
            'method'      => 'GET',
            'user_id'     => $this->owner->id,
            'device_type' => 'Desktop',
            'browser'     => 'Edge',
            'platform'    => 'Windows',
            'city'        => 'Dar es Salaam',
            'country'     => 'Tanzania',
        ]);

        VisitorLog::create([
            'ip_address'  => '8.8.8.8',
            'url'         => '/sales',
            'method'      => 'GET',
            'user_id'     => null,
            'device_type' => 'Mobile',
            'browser'     => 'Safari',
            'platform'    => 'iOS',
            'city'        => 'Ashburn',
            'country'     => 'United States',
        ]);

        // Get view page with a desktop user agent to ensure the test request itself is logged as Desktop
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ])->get(route('reports.visitors'));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'totalPageViews' => 3, // The current request is tracked *after* response is rendered, so only the 3 manually created logs exist during execution
            'uniqueVisitors' => 3, 
            'topDevice'      => 'Desktop',
            'topCountry'     => 'Tanzania',
        ]);
    }
}
