<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\LessonPlanReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonPlanSeeder extends Seeder
{
    public function run(): void
    {
        $reviewer = User::where('role', 'admin')->orderBy('id')->first();
        $coaches = CoachProfile::with('classTypes')->get();

        if ($coaches->isEmpty()) {
            return;
        }

        // Two pending per coach, so the approval queue is a queue rather than one row.
        $statuses = ['draft', 'pending', 'approved', 'rejected', 'pending', 'approved'];

        foreach ($coaches as $index => $coach) {
            $sessions = ClassSession::where('coach_profile_id', $coach->id)
                ->upcoming()
                ->orderBy('session_date')
                ->get();

            if ($sessions->isEmpty()) {
                continue;
            }

            foreach ($statuses as $offset => $status) {
                // Rotating the session spreads a coach's plans over the branches they teach at.
                $session = $sessions[($index + $offset) % $sessions->count()];

                $plan = LessonPlan::create([
                    'coach_profile_id' => $coach->id,
                    'branch_id' => $session->branch_id,
                    'class_type_id' => $session->class_type_id,
                    'class_session_id' => $offset % 2 === 0 ? $session->id : null,
                    'title' => $this->titles[($index + $offset) % count($this->titles)],
                    'objective' => 'Build steady breath and safe alignment through the sequence.',
                    'asana_sequence' => "Tadasana\nSurya Namaskar A x3\nVirabhadrasana II\nTrikonasana\nBalasana\nSavasana",
                    'duration_minutes' => [45, 60, 75, 90][($index + $offset) % 4],
                    'level' => LessonPlan::LEVELS[($index + $offset) % count(LessonPlan::LEVELS)],
                    'status' => $status,
                    'submitted_at' => $status === 'draft' ? null : now()->subDays($offset + 1),
                ]);

                if ($reviewer && in_array($status, ['approved', 'rejected'], true)) {
                    LessonPlanReview::create([
                        'lesson_plan_id' => $plan->id,
                        'reviewer_user_id' => $reviewer->id,
                        'action' => $status,
                        'comment' => $status === 'rejected'
                            ? 'Add a longer warm-up before the standing sequence.'
                            : null,
                        'reviewed_at' => now()->subDays($offset),
                    ]);
                }
            }
        }
    }

    private array $titles = [
        'Hip Mobility Flow',
        'Breathwork Foundations',
        'Yin Recovery for Athletes',
        'Gentle Morning Vinyasa',
        'Core and Balance',
        'Restorative Evening Wind-down',
    ];
}
