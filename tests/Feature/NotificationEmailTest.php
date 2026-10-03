<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ClassReminderNotification;
use App\Notifications\TuitionOverdueNotification;
use App\Providers\AppServiceProvider;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_mailing_event_reaches_the_inbox_through_the_notification_itself(): void
    {
        $user = $this->member();

        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $message = $this->sent();

        $this->assertNotNull($message);
        $this->assertSame([$user->email], array_keys($this->addresses($message)));
    }

    public function test_an_event_that_does_not_mail_sends_nothing(): void
    {
        $user = $this->member();

        Notification::send($user, new ClassReminderNotification(ClassSession::factory()->create()));

        $this->assertNull($this->sent());
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_the_message_renders_in_the_recipients_locale_with_no_raw_key(): void
    {
        $user = $this->member();
        $user->update(['locale' => 'vi']);

        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $subject = $this->sent()->getSubject();

        $this->assertStringNotContainsString('notifications.', $subject);
        $this->assertSame(__('notifications.tuitionOverdue.subject', [
            'invoice' => Invoice::first()->invoice_number,
        ], 'vi'), $subject);
    }

    public function test_always_to_redirects_every_recipient_away_from_the_seeded_addresses(): void
    {
        config(['mail.always_to' => 'demo-inbox@example.test']);
        (new AppServiceProvider($this->app))->boot();

        $user = $this->member();
        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $this->assertSame(['demo-inbox@example.test'], array_keys($this->addresses($this->sent())));
    }

    public function test_the_bell_row_records_whether_an_email_was_also_sent(): void
    {
        $user = $this->member();

        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));
        Notification::send($user, new ClassReminderNotification(ClassSession::factory()->create()));

        $rows = $user->notifications()->get()->keyBy(fn ($row) => $row->data['event']);

        $this->assertTrue($rows['tuition.overdue']->data['emailed']);
        $this->assertFalse($rows['class.reminder']->data['emailed']);
    }

    public function test_the_notification_page_hands_the_flag_to_the_view(): void
    {
        $user = $this->member();
        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $rows = $this->actingAs($user)->get('/cms/notifications')->inertiaProps('notifications')['data'];

        $this->assertTrue($rows[0]['emailed']);
    }

    private function sent()
    {
        $message = Mail::getSymfonyTransport()->messages()->last();

        return $message?->getOriginalMessage();
    }

    private function addresses($message): array
    {
        $out = [];

        foreach ($message->getTo() as $address) {
            $out[$address->getAddress()] = $address->getName();
        }

        return $out;
    }

    private function member(): User
    {
        $user = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function invoice(User $user): Invoice
    {
        return Invoice::factory()->create([
            'student_profile_id' => StudentProfile::where('user_id', $user->id)->value('id'),
        ]);
    }
}
