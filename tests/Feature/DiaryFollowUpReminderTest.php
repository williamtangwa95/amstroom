<?php

namespace Tests\Feature;

use App\Mail\DiaryFollowUpReminderMail;
use App\Models\Diary;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DiaryFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'owner',
        ]);
    }

    public function test_user_can_create_follow_up_with_days_before_and_email_preference(): void
    {
        $dueDate = now()->addDays(5)->toDateString();
        $expectedReminder = now()->addDays(3)->toDateString();

        $res = $this->actingAs($this->owner)->post(route('diary.store'), [
            'type'               => 'personal',
            'event_name'         => 'Supplier Payment Follow-up',
            'event_description'  => 'Confirm bank transfer receipt with supplier.',
            'event_date'         => now()->toDateString(),
            'due_date'           => $dueDate,
            'needs_follow_up'    => '1',
            'remind_days_before' => 2,
            'notify_email'       => '1',
        ]);

        $res->assertRedirect(route('diary.index'));

        $diary = Diary::latest('id')->first();
        $this->assertNotNull($diary);
        $this->assertTrue($diary->needs_follow_up);
        $this->assertEquals(2, $diary->remind_days_before);
        $this->assertEquals($expectedReminder, $diary->reminder_date->toDateString());
        $this->assertEquals('10:00', $diary->reminder_time_value);
        $this->assertTrue($diary->notify_email);
    }

    public function test_reminder_respects_custom_time_and_defaults_to_10am(): void
    {
        $this->travelTo(now()->setTime(9, 30));

        $diary = Diary::create([
            'user_id'            => $this->owner->id,
            'type'               => 'personal',
            'event_name'         => '10 AM Call',
            'event_description'  => 'Follow up at 10:00 AM.',
            'event_date'         => now()->subDays(2)->toDateString(),
            'due_date'           => now()->addDay()->toDateString(),
            'reminder_date'      => now()->toDateString(),
            'reminder_time'      => '10:00',
            'needs_follow_up'    => true,
            'remind_days_before' => 1,
            'notify_email'       => false,
        ]);

        // At 09:30 AM -> before 10:00 AM -> should NOT trigger yet
        $this->actingAs($this->owner)->getJson(route('diary.due-reminders'))
            ->assertOk()
            ->assertJsonCount(0, 'due_items');

        // At 10:00 AM -> triggers!
        $this->travelTo(now()->setTime(10, 0));
        $res = $this->actingAs($this->owner)->getJson(route('diary.due-reminders'));
        $res->assertOk()
            ->assertJsonCount(1, 'due_items')
            ->assertJsonPath('due_items.0.id', $diary->id)
            ->assertJsonPath('due_items.0.reminder_time', '10:00');
    }

    public function test_reminder_days_enforces_minimum_1_and_maximum_days_between_event_and_due_date(): void
    {
        $eventDate = now()->toDateString();
        $dueDate = now()->addDays(4)->toDateString(); // max allowed = 4 days

        // Reject 0 days (below minimum 1)
        $resZero = $this->actingAs($this->owner)->post(route('diary.store'), [
            'type'               => 'personal',
            'event_name'         => 'Invalid Zero Days',
            'event_description'  => 'Should fail validation.',
            'event_date'         => $eventDate,
            'due_date'           => $dueDate,
            'needs_follow_up'    => '1',
            'remind_days_before' => 0,
        ]);
        $resZero->assertSessionHasErrors('remind_days_before');

        // Reject 5 days (exceeds 4 days between event_date and due_date)
        $resOver = $this->actingAs($this->owner)->post(route('diary.store'), [
            'type'               => 'personal',
            'event_name'         => 'Invalid Over Max Days',
            'event_description'  => 'Should fail validation.',
            'event_date'         => $eventDate,
            'due_date'           => $dueDate,
            'needs_follow_up'    => '1',
            'remind_days_before' => 5,
        ]);
        $resOver->assertSessionHasErrors('remind_days_before');

        // Accept exact maximum (4 days = reminder on event_date)
        $resMax = $this->actingAs($this->owner)->post(route('diary.store'), [
            'type'               => 'personal',
            'event_name'         => 'Valid Max Days',
            'event_description'  => 'Should pass validation.',
            'event_date'         => $eventDate,
            'due_date'           => $dueDate,
            'needs_follow_up'    => '1',
            'remind_days_before' => 4,
        ]);
        $resMax->assertRedirect(route('diary.index'));
        $this->assertDatabaseHas('diaries', [
            'event_name'         => 'Valid Max Days',
            'remind_days_before' => 4,
        ]);
    }

    public function test_reminder_date_triggers_popup_once_in_app_notification_and_optional_email(): void
    {
        Mail::fake();

        $diary = Diary::create([
            'user_id'            => $this->owner->id,
            'type'               => 'personal',
            'event_name'         => 'Renew Shop License',
            'event_description'  => 'Submit renewal paperwork at municipal office.',
            'event_date'         => now()->subDay()->toDateString(),
            'due_date'           => now()->addDays(2)->toDateString(),
            'needs_follow_up'    => true,
            'remind_days_before' => 2,
            'reminder_date'      => now()->toDateString(),
            'notify_email'       => true,
        ]);

        // 1. First poll: returns popup item, generates 1 in-app notification & sends 1 email
        $res1 = $this->actingAs($this->owner)->getJson(route('diary.due-reminders'));
        $res1->assertOk();
        $res1->assertJsonCount(1, 'due_items');
        $res1->assertJsonPath('due_items.0.id', $diary->id);
        $res1->assertJsonPath('due_items.0.stage', 'reminder');

        $this->assertEquals(1, Notification::where('user_id', $this->owner->id)->where('title', 'Diary Follow-up Reminder')->count());
        Mail::assertSent(DiaryFollowUpReminderMail::class, 1);

        // 2. Clicking OK acknowledges the popup once
        $ackRes = $this->actingAs($this->owner)->postJson(route('diary.acknowledge', $diary), [
            'stage' => 'reminder',
        ]);
        $ackRes->assertOk()->assertJson(['success' => true]);

        // 3. Subsequent poll on same day does NOT show the popup again and does NOT duplicate notifications/email
        $res2 = $this->actingAs($this->owner)->getJson(route('diary.due-reminders'));
        $res2->assertOk()->assertJsonCount(0, 'due_items');
        $this->assertEquals(1, Notification::where('user_id', $this->owner->id)->count());
        Mail::assertSent(DiaryFollowUpReminderMail::class, 1);

        // 4. When due date arrives 2 days later, triggers due-date notification + popup once
        $this->travel(2)->days();

        $resDue = $this->actingAs($this->owner)->getJson(route('diary.due-reminders'));
        $resDue->assertOk();
        $resDue->assertJsonCount(1, 'due_items');
        $resDue->assertJsonPath('due_items.0.stage', 'due');

        $this->assertEquals(1, Notification::where('user_id', $this->owner->id)->where('title', 'Diary Follow-up Due Date Reached')->count());
        Mail::assertSent(DiaryFollowUpReminderMail::class, 2);
    }

    public function test_email_is_not_sent_when_notify_email_is_disabled(): void
    {
        Mail::fake();

        Diary::create([
            'user_id'            => $this->owner->id,
            'type'               => 'personal',
            'event_name'         => 'In-App Only Task',
            'event_description'  => 'No email should be sent for this.',
            'due_date'           => now()->toDateString(),
            'reminder_date'      => now()->toDateString(),
            'needs_follow_up'    => true,
            'remind_days_before' => 0,
            'notify_email'       => false,
        ]);

        $res = $this->actingAs($this->owner)->getJson(route('diary.due-reminders'));
        $res->assertOk()->assertJsonCount(1, 'due_items');

        $this->assertEquals(1, Notification::where('user_id', $this->owner->id)->count());
        Mail::assertNothingSent();
    }

    public function test_reschedule_updates_due_and_reminder_dates_and_resets_popup_flags(): void
    {
        $diary = Diary::create([
            'user_id'            => $this->owner->id,
            'type'               => 'personal',
            'event_name'         => 'Audit Inventory',
            'event_description'  => 'Monthly warehouse audit.',
            'due_date'           => now()->toDateString(),
            'reminder_date'      => now()->toDateString(),
            'needs_follow_up'    => true,
            'remind_days_before' => 0,
            'notify_email'       => false,
        ]);

        // Initial poll triggers due stage
        $this->actingAs($this->owner)->getJson(route('diary.due-reminders'))
            ->assertOk()
            ->assertJsonCount(1, 'due_items');

        // Reschedule to 5 days from now with reminder 2 days before (3 days from now)
        $newDue = now()->addDays(5)->toDateString();
        $newRem = now()->addDays(3)->toDateString();

        $rescheduleRes = $this->actingAs($this->owner)->postJson(route('diary.reschedule', $diary), [
            'due_date'      => $newDue,
            'reminder_date' => $newRem,
            'notify_email'  => true,
        ]);

        $rescheduleRes->assertOk()->assertJson(['success' => true]);

        $diary->refresh();
        $this->assertEquals($newDue, $diary->due_date->toDateString());
        $this->assertEquals($newRem, $diary->reminder_date->toDateString());
        $this->assertEquals(2, $diary->remind_days_before);
        $this->assertTrue($diary->notify_email);
        $this->assertNull($diary->reminder_notified_at);
        $this->assertNull($diary->due_notified_at);
        $this->assertNull($diary->reminder_popup_acknowledged_at);
        $this->assertNull($diary->due_popup_acknowledged_at);

        // No longer due today
        $this->actingAs($this->owner)->getJson(route('diary.due-reminders'))
            ->assertOk()
            ->assertJsonCount(0, 'due_items');
    }
}
