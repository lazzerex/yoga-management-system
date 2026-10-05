<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\InvoiceItem;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ResolveEntitlementAction
{
    public function forStudent(?StudentProfile $studentProfile): EntitlementSet
    {
        return new EntitlementSet($studentProfile ? $this->lines($studentProfile) : new Collection);
    }

    /** @return Collection<int, InvoiceItem> */
    private function lines(StudentProfile $studentProfile): Collection
    {
        return InvoiceItem::granting()
            ->whereHas('invoice', fn ($q) => $q->where('student_profile_id', $studentProfile->id))
            ->withCount(['enrollments as consumed_count' => fn ($q) => $q->consuming()])
            ->get();
    }
}
