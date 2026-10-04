<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\InvoiceItem;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEnrollmentAction
{
    public function __construct(private ResolveEntitlementAction $entitlements) {}

    /**
     * Lock order on every booking path: the session's enrollment rows, then the chosen
     * invoice_items row. The payment actions lock payments then invoices, so no cycle.
     */
    public function execute(StudentProfile $studentProfile, ClassSession $classSession): Enrollment
    {
        if ($classSession->status !== 'scheduled' || $classSession->session_date < now()->toDateString()) {
            throw ValidationException::withMessages([
                'action' => __('flash.enrollmentSessionUnavailable'),
            ]);
        }

        return DB::transaction(function () use ($studentProfile, $classSession) {
            $active = $classSession->enrollments()
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->get();

            if ($active->contains('student_profile_id', $studentProfile->id)) {
                throw ValidationException::withMessages([
                    'action' => __('flash.enrollmentAlreadyExists'),
                ]);
            }

            $bookedCount = $active->where('status', 'booked')->count();

            $set = $this->entitlements->forStudent($studentProfile);

            if ($reason = $set->check($classSession->session_date)) {
                throw ValidationException::withMessages(['action' => __($reason)]);
            }

            $invoiceItem = $set->pick($classSession->session_date);

            // Bookings on different sessions never meet on the rows above, so the quota needs its own lock.
            if ($invoiceItem && ! $invoiceItem->isUnlimited()) {
                $locked = InvoiceItem::whereKey($invoiceItem->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->sessionsRemaining() < 1) {
                    throw ValidationException::withMessages([
                        'action' => __('flash.enrollmentEntitlementExhausted'),
                    ]);
                }
            }

            return Enrollment::create([
                'student_profile_id' => $studentProfile->id,
                'class_session_id' => $classSession->id,
                'invoice_item_id' => $invoiceItem?->id,
                'status' => $bookedCount < $classSession->capacity ? 'booked' : 'waitlisted',
                'enrolled_at' => now(),
            ]);
        });
    }
}
