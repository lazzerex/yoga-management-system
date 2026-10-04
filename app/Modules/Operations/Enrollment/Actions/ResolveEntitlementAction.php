<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\InvoiceItem;
use App\Models\StudentProfile;
use App\Support\Settings;
use Illuminate\Support\Collection;

/** The only place booking.require_entitlement is read. Callers take the answer from the set. */
class ResolveEntitlementAction
{
    public function forStudent(?StudentProfile $studentProfile): EntitlementSet
    {
        $enforced = (bool) Settings::get('booking.require_entitlement', config('enrollment.require_entitlement'));

        return new EntitlementSet($enforced, $studentProfile ? $this->lines($studentProfile) : new Collection);
    }

    /** @return Collection<int, InvoiceItem> */
    private function lines(StudentProfile $studentProfile): Collection
    {
        return InvoiceItem::granting()
            ->whereHas('invoice', fn ($q) => $q->where('student_profile_id', $studentProfile->id))
            ->withCount(['enrollments as consumed_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->get();
    }
}
