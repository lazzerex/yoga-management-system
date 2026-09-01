<?php

namespace App\Modules\Operations\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkStudentAttendanceAction
{
    public function execute(ClassSession $classSession, array $entries, User $marker): int
    {
        $bookedIds = $classSession->enrollments()
            ->where('status', 'booked')
            ->pluck('id')
            ->all();

        $marked = 0;

        DB::transaction(function () use ($entries, $bookedIds, $marker, &$marked) {
            foreach ($entries as $entry) {
                if (! in_array((int) $entry['enrollment_id'], $bookedIds, true)) {
                    continue;
                }

                StudentAttendance::updateOrCreate(
                    ['enrollment_id' => $entry['enrollment_id']],
                    [
                        'status' => $entry['status'],
                        'notes' => $entry['notes'] ?? null,
                        'marked_by_user_id' => $marker->id,
                        'marked_at' => now(),
                    ]
                );

                $marked++;
            }
        });

        return $marked;
    }
}
