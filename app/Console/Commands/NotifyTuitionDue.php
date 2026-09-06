<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\TuitionDueSoonNotification;
use App\Notifications\TuitionOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotifyTuitionDue extends Command
{
    protected $signature = 'notify:tuition-due {--dry-run : List recipients and send nothing} {--limit= : Stop after this many messages}';

    protected $description = 'Remind students about tuition invoices falling due in three days, and about invoices already overdue';

    private const REMINDER_DAYS = 3;

    private const OVERDUE_EVERY_DAYS = 7;

    public function handle(): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $sent = 0;

        foreach ($this->dueSoon() as $invoice) {
            if ($limit !== null && $sent >= $limit) {
                break;
            }

            $sent += $this->deliver($invoice, 'tuition.due_soon', new TuitionDueSoonNotification($invoice));
        }

        foreach ($this->overdue() as $invoice) {
            if ($limit !== null && $sent >= $limit) {
                break;
            }

            $sent += $this->deliver($invoice, 'tuition.overdue', new TuitionOverdueNotification($invoice));
        }

        $this->info($this->option('dry-run')
            ? "Dry run: {$sent} message(s) would be sent."
            : "Sent {$sent} tuition reminder(s).");

        return self::SUCCESS;
    }

    private function dueSoon()
    {
        return Invoice::open()
            ->whereDate('due_date', today()->addDays(self::REMINDER_DAYS))
            ->with('studentProfile.user')
            ->get();
    }

    private function overdue()
    {
        return Invoice::overdue()->with('studentProfile.user')->get();
    }

    private function deliver(Invoice $invoice, string $eventKey, $notification): int
    {
        $user = $invoice->studentProfile?->user;

        if (! $user) {
            return 0;
        }

        if ($this->alreadySent($eventKey, $user->id, $invoice->id)) {
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->line("  {$eventKey}  {$invoice->invoice_number}  {$user->email}");

            return 1;
        }

        Notification::send($user, $notification);

        DB::table('notification_dispatches')->insertOrIgnore([
            'event_key' => $eventKey,
            'notifiable_id' => $user->id,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'sent_on' => today()->toDateString(),
        ]);

        return 1;
    }

    // Due-soon once a day, overdue once a week.
    private function alreadySent(string $eventKey, int $userId, int $invoiceId): bool
    {
        $since = $eventKey === 'tuition.overdue'
            ? today()->subDays(self::OVERDUE_EVERY_DAYS - 1)
            : today();

        return DB::table('notification_dispatches')
            ->where('event_key', $eventKey)
            ->where('notifiable_id', $userId)
            ->where('subject_type', Invoice::class)
            ->where('subject_id', $invoiceId)
            ->whereDate('sent_on', '>=', $since->toDateString())
            ->exists();
    }
}
