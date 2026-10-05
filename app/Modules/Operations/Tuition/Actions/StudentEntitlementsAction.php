<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\InvoiceItem;

/** Reports what a student holds. It never decides whether booking is gated; ResolveEntitlementAction does. */
class StudentEntitlementsAction
{
    public function execute(?int $studentProfileId): array
    {
        if (! $studentProfileId) {
            return [];
        }

        return InvoiceItem::granting()
            ->with('tuitionPlan:id,name,name_vi')
            ->whereHas('invoice', fn ($q) => $q->where('student_profile_id', $studentProfileId))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->withCount(['enrollments as consumed_count' => fn ($q) => $q->consuming()])
            ->orderByRaw('valid_until is null, valid_until')
            ->get()
            ->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->label(),
                'valid_from' => $item->valid_from?->toDateString(),
                'valid_until' => $item->valid_until?->toDateString(),
                'sessions_granted' => $item->sessions_granted,
                'sessions_remaining' => $item->sessionsRemaining(),
            ])
            ->all();
    }
}
