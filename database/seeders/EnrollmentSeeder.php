<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\InvoiceItem;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $studentIds = StudentProfile::active()->pluck('id');
        $branchIds = Branch::active()->orderBy('name')->pluck('id');

        if ($studentIds->count() < 2 || $branchIds->isEmpty()) {
            return;
        }

        // Members belong to a home branch and only occasionally practise elsewhere,
        // which is what makes the per-branch student counts differ at all.
        $homeBranch = $studentIds->mapWithKeys(fn (int $id, int $index) => [
            $id => $branchIds[$index % $branchIds->count()],
        ]);

        $sessions = ClassSession::whereIn('status', ['scheduled', 'done'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $today = now()->toDateString();
        $passes = $this->currentPasses();
        $rows = [];

        foreach ($sessions as $index => $session) {
            // Fill varies by weekday and time of day as well as by session, so the
            // occupancy heat map has hot and cold cells rather than one flat colour.
            $hour = (int) substr($session->start_time, 0, 2);
            $weekday = (int) date('w', strtotime($session->session_date));
            $fillRatio = match (true) {
                $index % 11 === 0 => 1.35,                     // a waitlist every so often
                $hour >= 17 => 0.85 + ($index % 3) * 0.05,     // evenings are busy
                $weekday === 0 || $weekday === 6 => 0.7,       // weekends middling
                $hour <= 7 => 0.45 + ($index % 4) * 0.08,      // early mornings quieter
                default => 0.55 + ($index % 5) * 0.06,
            };

            $target = min($studentIds->count(), (int) ceil($session->capacity * $fillRatio));
            $locals = $studentIds->filter(fn (int $id) => $homeBranch[$id] === $session->branch_id)->shuffle();
            // Only every third member ever travels, so the per-branch rosters stay distinct.
            $visitors = $studentIds
                ->filter(fn (int $id) => $homeBranch[$id] !== $session->branch_id && $id % 3 === 0)
                ->shuffle();

            // Four in five seats go to members of this branch; the rest are visitors.
            $localShare = (int) ceil($target * 0.8);
            $picked = $locals->take($localShare)->merge($visitors->take($target - $localShare));

            // An oversubscribed session draws on the whole centre, which is the only
            // way a waitlist forms once members mostly stay at their home branch.
            if ($picked->count() < $target) {
                $picked = $picked->merge($studentIds->diff($picked)->shuffle()->take($target - $picked->count()));
            }

            $picked = $picked->values();
            $booked = 0;

            foreach ($picked as $offset => $studentId) {
                $status = $booked < $session->capacity ? 'booked' : 'waitlisted';

                // Roughly one booking in eleven is dropped again, so the bookings board
                // and its status filter have cancellations to show.
                $cancelled = ($session->id * 7 + $offset) % 11 === 0;
                $enrolledAt = now()->subDays(7)->addMinutes($offset * 3);

                $rows[] = [
                    'student_profile_id' => $studentId,
                    'class_session_id' => $session->id,
                    'invoice_item_id' => $cancelled || $session->session_date < $today
                        ? null
                        : $this->spend($passes, $studentId),
                    'status' => $cancelled ? 'cancelled' : $status,
                    'enrolled_at' => $enrolledAt,
                    'cancelled_at' => $cancelled ? $enrolledAt->copy()->addDays(1) : null,
                    'created_at' => $enrolledAt,
                    'updated_at' => $cancelled ? $enrolledAt->copy()->addDays(1) : $enrolledAt,
                ];

                if (! $cancelled && $status === 'booked') {
                    $booked++;
                }
            }
        }

        // Bulk insert, not CreateEnrollmentAction: ~10k rows, and the lines are already decided above.
        collect($rows)->chunk(500)->each(fn ($chunk) => Enrollment::insert($chunk->all()));
    }

    /**
     * Lines each student can book a forward session against. A pack keeps two sessions
     * back so the counter never demos as empty.
     *
     * @return array<int, array{unlimited: int|null, pack: int|null, packLeft: int}>
     */
    private function currentPasses(): array
    {
        $passes = [];

        $lines = InvoiceItem::granting()
            ->with('invoice:id,student_profile_id')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->orderByRaw('valid_until is null desc, valid_until desc')
            ->get();

        foreach ($lines as $line) {
            $studentId = $line->invoice->student_profile_id;
            $passes[$studentId] ??= ['unlimited' => null, 'pack' => null, 'packLeft' => 0];

            if ($line->isUnlimited()) {
                $passes[$studentId]['unlimited'] ??= $line->id;

                continue;
            }

            if ($passes[$studentId]['pack'] === null) {
                $passes[$studentId]['pack'] = $line->id;
                $passes[$studentId]['packLeft'] = max(0, $line->sessions_granted - 2);
            }
        }

        return $passes;
    }

    /** Spends a pack session while one is held, then falls back to the unlimited pass. */
    private function spend(array &$passes, int $studentId): ?int
    {
        if (! isset($passes[$studentId])) {
            return null;
        }

        if ($passes[$studentId]['packLeft'] > 0) {
            $passes[$studentId]['packLeft']--;

            return $passes[$studentId]['pack'];
        }

        return $passes[$studentId]['unlimited'];
    }
}
