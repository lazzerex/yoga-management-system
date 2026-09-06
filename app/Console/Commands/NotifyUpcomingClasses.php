<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Notifications\ClassReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotifyUpcomingClasses extends Command
{
    protected $signature = 'notify:upcoming-classes {--dry-run : List recipients and send nothing} {--limit= : Stop after this many messages}';

    protected $description = 'Remind booked members and the assigned coach about tomorrow\'s classes';

    public function handle(): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $sent = 0;

        $sessions = ClassSession::whereDate('session_date', today()->addDay())
            ->where('status', 'scheduled')
            ->with(['classType', 'coachProfile.user', 'enrollments' => fn ($q) => $q->where('status', 'booked')->with('studentProfile.user')])
            ->get();

        foreach ($sessions as $session) {
            foreach ($this->recipients($session) as $user) {
                if ($limit !== null && $sent >= $limit) {
                    break 2;
                }

                $sent += $this->deliver($session, $user);
            }
        }

        $this->info($this->option('dry-run')
            ? "Dry run: {$sent} message(s) would be sent."
            : "Sent {$sent} class reminder(s).");

        return self::SUCCESS;
    }

    private function recipients(ClassSession $session): array
    {
        $members = $session->enrollments
            ->map(fn ($enrollment) => $enrollment->studentProfile?->user)
            ->all();

        return array_values(array_filter([...$members, $session->coachProfile?->user]));
    }

    private function deliver(ClassSession $session, $user): int
    {
        if ($this->alreadySent($user->id, $session->id)) {
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->line("  class.reminder  session {$session->id}  {$user->email}");

            return 1;
        }

        Notification::send($user, new ClassReminderNotification($session));

        DB::table('notification_dispatches')->insertOrIgnore([
            'event_key' => 'class.reminder',
            'notifiable_id' => $user->id,
            'subject_type' => ClassSession::class,
            'subject_id' => $session->id,
            'sent_on' => today()->toDateString(),
        ]);

        return 1;
    }

    private function alreadySent(int $userId, int $sessionId): bool
    {
        return DB::table('notification_dispatches')
            ->where('event_key', 'class.reminder')
            ->where('notifiable_id', $userId)
            ->where('subject_type', ClassSession::class)
            ->where('subject_id', $sessionId)
            ->whereDate('sent_on', today()->toDateString())
            ->exists();
    }
}
