<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = ClassSession::with('enrollments')
            ->where('status', 'done')
            ->where('session_date', '<', now()->toDateString())
            ->get();

        foreach ($sessions as $index => $session) {
            $start = Carbon::parse($session->session_date.' '.$session->start_time);
            $end = Carbon::parse($session->session_date.' '.$session->end_time);

            // Every seventh session has no check-in, so the reports show gaps.
            if ($index % 7 !== 0) {
                TeacherAttendance::create([
                    'class_session_id' => $session->id,
                    'coach_profile_id' => $session->coach_profile_id,
                    'checked_in_at' => $start->copy()->subMinutes($index % 3 === 0 ? -6 : 8),
                    'checked_out_at' => $end->copy()->addMinutes(4),
                ]);
            }

            foreach ($session->enrollments->where('status', 'booked') as $offset => $enrollment) {
                $roll = ($index + $offset) % 10;
                $status = match (true) {
                    $roll === 0 => 'absent',
                    $roll <= 2 => 'late',
                    default => 'present',
                };

                StudentAttendance::create([
                    'enrollment_id' => $enrollment->id,
                    'status' => $status,
                    'marked_at' => $end,
                ]);
            }
        }
    }
}
