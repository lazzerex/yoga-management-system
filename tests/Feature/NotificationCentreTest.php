<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ClassReminderNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCentreTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_viewer_sees_only_their_own_notifications(): void
    {
        [$mine] = $this->memberWithNotification();
        [$theirs] = $this->memberWithNotification();

        $this->actingAs($mine)
            ->get('/cms/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->where('notifications.total', 1));

        $this->assertSame(1, $theirs->notifications()->count());
    }

    public function test_a_viewer_cannot_read_someone_elses_notification(): void
    {
        [$mine] = $this->memberWithNotification();
        [$theirs] = $this->memberWithNotification();
        $foreign = $theirs->notifications()->first();

        $this->actingAs($mine)
            ->post("/cms/notifications/{$foreign->id}/read")
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_marking_all_read_clears_the_unread_count(): void
    {
        [$user] = $this->memberWithNotification();
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)->post('/cms/notifications/read-all')->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_clearing_removes_every_notification_the_viewer_owns(): void
    {
        [$mine] = $this->memberWithNotification();
        [$theirs] = $this->memberWithNotification();

        $this->actingAs($mine)->delete('/cms/notifications')->assertRedirect();

        $this->assertSame(0, $mine->fresh()->notifications()->count());
        $this->assertSame(1, $theirs->fresh()->notifications()->count(), 'clearing is scoped to the viewer');
    }

    public function test_a_guest_cannot_clear_notifications(): void
    {
        [$user] = $this->memberWithNotification();

        $this->delete('/cms/notifications')->assertRedirect('/cms/login');

        $this->assertSame(1, $user->fresh()->notifications()->count());
    }

    public function test_the_unread_count_is_shared_with_every_page(): void
    {
        [$user] = $this->memberWithNotification();

        $this->actingAs($user)
            ->get('/cms/profile')
            ->assertInertia(fn (Assert $page) => $page->where('bell.unread', 1));
    }

    public function test_the_recent_list_is_withheld_until_the_bell_asks_for_it(): void
    {
        [$user] = $this->memberWithNotification();

        $this->actingAs($user)
            ->get('/cms/profile')
            ->assertInertia(fn (Assert $page) => $page->missing('bell.recent'));

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Inertia::getVersion(),
                'X-Inertia-Partial-Component' => 'Profile/Show',
                'X-Inertia-Partial-Data' => 'bell',
            ])
            ->get('/cms/profile')
            ->assertOk();

        $response->assertJsonCount(1, 'props.bell.recent');
        $response->assertJsonPath('props.bell.unread', 1);
        $response->assertJsonPath('props.bell.recent.0.message', 'notifications.classReminder.bell');
        $response->assertJsonPath('props.bell.recent.0.read', false);
    }

    public function test_the_bell_still_works_while_standing_on_the_notifications_page(): void
    {
        [$user] = $this->memberWithNotification();

        $this->actingAs($user)->get('/cms/notifications')->assertOk();

        // Page props merge over shared ones, so a same-named shared prop is swallowed here.
        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Inertia::getVersion(),
                'X-Inertia-Partial-Component' => 'Notifications/Index',
                'X-Inertia-Partial-Data' => 'bell,flash,errors',
            ])
            ->get('/cms/notifications')
            ->assertOk();

        $response->assertJsonPath('props.bell.unread', 1);
        $response->assertJsonCount(1, 'props.bell.recent');
    }

    public function test_the_bell_refresh_does_not_carry_a_spent_flash_message(): void
    {
        [$user] = $this->memberWithNotification();

        $this->actingAs($user)
            ->post('/cms/profile/notification-preferences', ['preferences' => []])
            ->assertRedirect();

        $this->actingAs($user)->get('/cms/profile')->assertInertia(fn (Assert $page) => $page
            ->where('flash.success.key', 'flash.notificationPreferencesUpdated'));

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Inertia::getVersion(),
                'X-Inertia-Partial-Component' => 'Profile/Show',
                'X-Inertia-Partial-Data' => 'bell,flash,errors',
            ])
            ->get('/cms/profile')
            ->assertOk();

        $response->assertJsonPath('props.flash.success', null);
        $response->assertJsonCount(1, 'props.bell.recent');
    }

    public function test_a_member_sees_only_the_events_that_can_reach_them(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)
            ->get('/cms/profile')
            ->assertInertia(function (Assert $page) {
                $keys = collect($page->toArray()['props']['notificationEvents'])->pluck('key');

                $this->assertContains('tuition.overdue', $keys);
                $this->assertNotContains('member.registered', $keys);
                $this->assertNotContains('lesson_plan.submitted', $keys);
            });
    }

    public function test_switching_a_channel_off_stops_that_notification(): void
    {
        [$user, $profile] = $this->member();

        $this->actingAs($user)->post('/cms/profile/notification-preferences', [
            'preferences' => [
                'tuition.overdue' => ['mail' => false, 'database' => false],
            ],
        ])->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->wantsNotification('tuition.overdue', 'database'));
        $this->assertFalse($user->wantsNotification('tuition.overdue', 'mail'));

        Notification::fake();
        Invoice::factory()->overdue()->create(['student_profile_id' => $profile->id, 'status' => 'unpaid']);

        $this->artisan('notify:tuition-due')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_only_deviations_from_the_catalogue_are_stored(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)->post('/cms/profile/notification-preferences', [
            'preferences' => [
                'tuition.overdue' => ['database' => false],
                'tuition.due_soon' => ['database' => true],
            ],
        ]);

        $this->assertSame(['tuition.overdue' => ['database' => false]], $user->fresh()->notification_preferences);
    }

    public function test_a_preference_for_a_channel_that_is_not_turned_on_is_ignored(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)->post('/cms/profile/notification-preferences', [
            'preferences' => [
                'tuition.overdue' => ['sms' => false],
            ],
        ]);

        $this->assertSame([], $user->fresh()->notification_preferences);
    }

    public function test_a_viewer_cannot_store_a_preference_for_an_event_that_never_reaches_them(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)->post('/cms/profile/notification-preferences', [
            'preferences' => [
                'member.registered' => ['mail' => false, 'database' => false],
                'not.an.event' => ['mail' => false],
            ],
        ]);

        $this->assertSame([], $user->fresh()->notification_preferences);
    }

    private function member(): array
    {
        $user = User::factory()->create(['role' => 'member']);

        return [$user, StudentProfile::factory()->create(['user_id' => $user->id])];
    }

    private function memberWithNotification(): array
    {
        [$user, $profile] = $this->member();
        $user->notify(new ClassReminderNotification(ClassSession::factory()->create()));

        return [$user->fresh(), $profile];
    }
}
