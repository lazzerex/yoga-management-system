<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Attendance a student keeps, by student id modulo five. Spreading it this way is
     * what gives the People view's distribution chart more than one filled band.
     */
    private const RELIABILITY = [0 => 45, 1 => 68, 2 => 80, 3 => 92, 4 => 99];

    public function run(): void
    {
        $sessions = ClassSession::with('enrollments')
            ->where('status', 'done')
            ->where('session_date', '<', now()->toDateString())
            ->orderBy('session_date')
            ->get();

        $teacherRows = [];
        $studentRows = [];

        foreach ($sessions as $index => $session) {
            $start = Carbon::parse($session->session_date.' '.$session->start_time);
            $end = Carbon::parse($session->session_date.' '.$session->end_time);

            // Every seventh session has no check-in, so the reports show gaps.
            if ($index % 7 !== 0) {
                $teacherRows[] = [
                    'class_session_id' => $session->id,
                    'coach_profile_id' => $session->coach_profile_id,
                    'checked_in_at' => $start->copy()->subMinutes($index % 3 === 0 ? -6 : 8),
                    'checked_out_at' => $end->copy()->addMinutes(4),
                    'notes' => null,
                    'created_at' => $end,
                    'updated_at' => $end,
                ];
            }

            foreach ($session->enrollments->where('status', 'booked') as $offset => $enrollment) {
                $studentRows[] = [
                    'enrollment_id' => $enrollment->id,
                    'status' => $this->status($enrollment, $index + $offset),
                    'notes' => null,
                    'marked_by_user_id' => null,
                    'marked_at' => $end,
                    'created_at' => $end,
                    'updated_at' => $end,
                ];
            }
        }

        // Batched: a full year of history is a few thousand rows, and one insert per
        // row turns the seed into a minute of waiting.
        collect($teacherRows)->chunk(500)->each(fn ($chunk) => TeacherAttendance::insert($chunk->all()));
        collect($studentRows)->chunk(500)->each(fn ($chunk) => StudentAttendance::insert($chunk->all()));
    }

    /** Deterministic, so a re-seed produces the same demo figures. */
    private function status(Enrollment $enrollment, int $seed): string
    {
        $target = self::RELIABILITY[$enrollment->student_profile_id % 5];
        $roll = ($enrollment->id * 37 + $seed * 11) % 100;

        if ($roll >= $target) {
            return 'absent';
        }

        return $roll % 9 === 0 ? 'late' : 'present';
    }
}
