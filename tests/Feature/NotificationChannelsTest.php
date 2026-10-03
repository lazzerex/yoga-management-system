<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ClassReminderNotification;
use App\Notifications\TuitionOverdueNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_only_the_channels_the_project_has_turned_on_are_used(): void
    {
        $this->assertSame(['database', 'mail'], config('notifications.channels'));
    }

    public function test_email_is_a_subset_of_the_bell_not_a_mirror(): void
    {
        [$user] = $this->member();

        $reminder = new ClassReminderNotification(ClassSession::factory()->create());
        $overdue = new TuitionOverdueNotification($this->invoice($user));

        $this->assertSame(['database'], $reminder->via($user));
        $this->assertSame(['database', 'mail'], $overdue->via($user));
    }

    public function test_a_recipient_can_switch_mail_off_and_keep_the_bell(): void
    {
        [$user] = $this->member();
        $user->update(['notification_preferences' => ['tuition.overdue' => ['mail' => false]]]);

        $notification = new TuitionOverdueNotification($this->invoice($user));

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_the_database_channel_runs_inline_so_no_worker_is_needed(): void
    {
        $notification = new ClassReminderNotification(ClassSession::factory()->create());

        $this->assertSame('sync', $notification->viaConnections()['database']);
    }

    public function test_an_event_that_never_mails_queues_nothing_at_all(): void
    {
        config(['queue.default' => 'database']);

        [$user] = $this->member();
        $user->notify(new ClassReminderNotification(ClassSession::factory()->create()));

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_a_staff_cancellation_writes_the_bell_row_without_a_worker(): void
    {
        // Queue everything by default, as the dev .env does.
        config(['queue.default' => 'database']);

        $session = ClassSession::factory()->create(['session_date' => now()->addDays(5)->toDateString()]);
        [$member, $profile] = $this->member();
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $profile->id,
            'status' => 'booked',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/cms/operations/enrollments/{$enrollment->id}");

        $this->assertSame(1, $member->notifications()->count());
        $this->assertSame(1, $member->unreadNotifications()->count());
        $this->assertSame(1, DB::table('jobs')->count(), 'only the mail leg should wait on a worker');
    }

    public function test_the_bell_row_carries_what_the_popover_renders(): void
    {
        [$user] = $this->member();
        $user->notify(new ClassReminderNotification(ClassSession::factory()->create()));

        $data = $user->notifications()->first()->data;

        $this->assertSame('class.reminder', $data['event']);
        $this->assertSame('notifications.classReminder.bell', $data['message']);
        $this->assertArrayHasKey('class', $data['params']);
        $this->assertNotEmpty($data['url']);
    }

    public function test_the_preference_card_offers_both_live_channels(): void
    {
        [$user] = $this->member();

        $response = $this->actingAs($user)->get('/cms/profile');

        $this->assertSame(['database', 'mail'], $response->inertiaProps('notificationChannels'));

        foreach ($response->inertiaProps('notificationEvents') as $event) {
            $this->assertSame(['mail', 'database'], array_keys($event['channels']), "{$event['key']} offers a dead switch");
        }
    }

    private function member(): array
    {
        $user = User::factory()->create(['role' => 'member']);

        return [$user, StudentProfile::factory()->create(['user_id' => $user->id])];
    }

    private function invoice(User $user): Invoice
    {
        return Invoice::factory()->create([
            'student_profile_id' => StudentProfile::where('user_id', $user->id)->value('id'),
        ]);
    }
}
