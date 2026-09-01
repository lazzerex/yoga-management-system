<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $studentIds = StudentProfile::active()->pluck('id');

        if ($studentIds->count() < 2) {
            return;
        }

        $sessions = ClassSession::whereIn('status', ['scheduled', 'done'])
            ->orderBy('session_date')
            ->get();

        foreach ($sessions as $index => $session) {
            // Every third session is deliberately pushed over capacity to seed a waitlist.
            $fillRatio = $index % 3 === 0 ? 1.4 : (0.3 + ($index % 5) * 0.15);
            $target = min($studentIds->count(), (int) ceil($session->capacity * $fillRatio));

            $picked = $studentIds->shuffle()->take($target)->values();
            $booked = 0;

            foreach ($picked as $offset => $studentId) {
                $status = $booked < $session->capacity ? 'booked' : 'waitlisted';

                Enrollment::create([
                    'student_profile_id' => $studentId,
                    'class_session_id' => $session->id,
                    'status' => $status,
                    'enrolled_at' => now()->subDays(7)->addMinutes($offset * 3),
                ]);

                if ($status === 'booked') {
                    $booked++;
                }
            }
        }
    }
}
