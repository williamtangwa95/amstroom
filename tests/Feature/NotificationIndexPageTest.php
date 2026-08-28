<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationIndexPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->user = User::create([
            'name'     => 'Test User',
            'email'    => 'notif_test@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);
    }

    public function test_notification_index_page_loads_with_pagination_without_rendering_errors()
    {
        // Create 20 notifications to trigger pagination (15 per page)
        for ($i = 1; $i <= 20; $i++) {
            Notification::create([
                'user_id' => $this->user->id,
                'title'   => "Test Notification #{$i}",
                'message' => "This is test notification description #{$i}",
                'is_read' => false,
            ]);
        }

        $this->actingAs($this->user);

        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Notification Center');
        $response->assertSee('pagination');
    }
}
