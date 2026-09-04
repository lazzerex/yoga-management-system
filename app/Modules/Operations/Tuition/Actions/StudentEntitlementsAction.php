<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\InvoiceItem;

/** Shared by the membership page and the member dashboard. */
class StudentEntitlementsAction
{
    /** Only paid lines grant access, and an undated line (a one-off charge) grants nothing ongoing. */
    public function execute(?int $studentProfileId): array
    {
        if (! $studentProfileId) {
            return [];
        }

        return InvoiceItem::whereHas('invoice', fn ($q) => $q
            ->where('student_profile_id', $studentProfileId)
            ->where('status', 'paid'))
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', today())
            ->orderBy('valid_until')
            ->get()
            ->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'valid_from' => $item->valid_from?->toDateString(),
                'valid_until' => $item->valid_until->toDateString(),
                'sessions_granted' => $item->sessions_granted,
            ])
            ->all();
    }
}
