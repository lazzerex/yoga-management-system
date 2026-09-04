<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Modules\Operations\Tuition\Actions\StudentEntitlementsAction;

/**
 * A member holds no operations.* permission at all, so every widget here is gated on
 * member.dashboard.view plus their own student profile.
 */
class MemberWidgetsAction
{
    public function __construct(private StudentEntitlementsAction $entitlements) {}

    public function execute(User $user): array
    {
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return [];
        }

        return [
            'upcoming' => $this->upcoming($studentProfileId),
            'attendanceRate' => $this->attendanceRate($studentProfileId),
            'membership' => [
                'outstanding' => $this->outstanding($studentProfileId),
                'entitlements' => $this->entitlements->execute($studentProfileId),
                'url' => route('member.my-membership'),
            ],
        ];
    }

    private function upcoming(int $studentProfileId): array
    {
        $sessions = Enrollment::with(['classSession.classType:id,name', 'classSession.branch:id,name', 'classSession.room:id,name'])
            ->where('student_profile_id', $studentProfileId)
            ->where('status', 'booked')
            ->whereHas('classSession', fn ($q) => $q
                ->where('status', 'scheduled')
                ->where('session_date', '>=', today()->toDateString()))
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->classSession->session_date.$enrollment->classSession->start_time)
            ->values();

        return [
            'count' => $sessions->count(),
            'items' => $sessions->take(5)->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'session_date' => $enrollment->classSession->session_date,
                'start_time' => substr($enrollment->classSession->start_time, 0, 5),
                'class_type_name' => $enrollment->classSession->classType->name,
                'branch_name' => $enrollment->classSession->branch->name,
                'room_name' => $enrollment->classSession->room->name,
            ])->all(),
            'url' => route('member.my-classes'),
        ];
    }

    private function attendanceRate(int $studentProfileId): array
    {
        $marks = StudentAttendance::whereHas('enrollment', fn ($q) => $q->where('student_profile_id', $studentProfileId))
            ->get(['status']);

        $total = $marks->count();
        $attended = $marks->whereIn('status', ['present', 'late'])->count();

        return [
            'total' => $total,
            'attended' => $attended,
            'rate' => $total > 0 ? (int) round($attended / $total * 100) : 0,
        ];
    }

    private function outstanding(int $studentProfileId): int
    {
        return (int) Invoice::where('student_profile_id', $studentProfileId)
            ->open()
            ->withSum('recordedPayments', 'amount')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->balance());
    }
}
