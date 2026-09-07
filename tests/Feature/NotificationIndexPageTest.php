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

    public function test_notification_index_page_filters_by_search_query()
    {
        Notification::create([
            'user_id' => $this->user->id,
            'title'   => 'Stock Transfer Approved',
            'message' => 'Transfer #42 has been approved by Owner.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'title'   => 'Low Stock Alert',
            'message' => 'Item Dell XPS 15 is running low.',
            'is_read' => false,
        ]);

        $this->actingAs($this->user);

        // Search for "Transfer"
        $response = $this->get(route('notifications.index', ['search' => 'Transfer']));

        $response->assertStatus(200);
        $results = $response->viewData('notifications');
        $this->assertEquals(1, $results->total());
        $this->assertEquals('Stock Transfer Approved', $results->first()->title);

        // Search for non-existent keyword
        $noResultResponse = $this->get(route('notifications.index', ['search' => 'NonExistentKeywordXYZ']));
        $noResultResponse->assertStatus(200);
        $noResults = $noResultResponse->viewData('notifications');
        $this->assertEquals(0, $noResults->total());
    }
}
