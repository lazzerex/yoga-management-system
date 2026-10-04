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
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;
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

    /** The shell is string keys from lang/vi.json, not lang/vi/notifications.php. */
    public function test_the_mail_shell_is_translated_and_not_left_in_english(): void
    {
        $user = $this->member();
        $user->update(['locale' => 'vi']);

        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $body = $this->sent()->getHtmlBody();

        $this->assertStringContainsString('Xin chào!', $body);
        $this->assertStringContainsString('Trân trọng,', $body);
        $this->assertStringContainsString('Mọi quyền được bảo lưu.', $body);
        $this->assertStringContainsString('vào trình duyệt của bạn', $body);

        $this->assertStringNotContainsString('Hello!', $body);
        $this->assertStringNotContainsString('Regards,', $body);
        $this->assertStringNotContainsString('All rights reserved.', $body);
        $this->assertStringNotContainsString('having trouble clicking', $body);
    }

    public function test_an_english_recipient_still_gets_the_english_shell(): void
    {
        $user = $this->member();
        $user->update(['locale' => 'en']);

        Notification::send($user, new TuitionOverdueNotification($this->invoice($user)));

        $body = $this->sent()->getHtmlBody();

        $this->assertStringContainsString('Hello!', $body);
        $this->assertStringContainsString('Regards,', $body);
        $this->assertStringNotContainsString('Xin chào!', $body);
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

    // MAIL_MAILER=array in phpunit.xml, so the transport is the one that keeps messages.
    private function sent(): ?Email
    {
        /** @var ArrayTransport $transport */
        $transport = Mail::getSymfonyTransport();

        $message = $transport->messages()->last()?->getOriginalMessage();

        return $message instanceof Email ? $message : null;
    }

    /** @return array<string, string|null> */
    private function addresses(Email $message): array
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
