<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use Illuminate\Database\Seeder;

/**
 * Generated sessions are all identical to their template. Real timetables are not:
 * some evenings are called off and some are moved or resized on the day. Runs after
 * the bookings, so a cancelled class still shows who had booked it.
 */
class SessionVarietySeeder extends Seeder
{
    public function run(): void
    {
        $sessions = ClassSession::orderBy('id')->get(['id', 'capacity', 'status', 'session_date']);

        foreach ($sessions as $session) {
            if ($session->id % 19 === 0) {
                $session->update(['status' => 'cancelled']);

                continue;
            }

            // A one-off change to a single session, which is how holidays and room
            // swaps are handled: the weekly template is left alone.
            if ($session->id % 17 === 0) {
                $session->update([
                    'capacity' => max(4, $session->capacity - 4),
                    'is_overridden' => true,
                ]);
            }
        }
    }
}
