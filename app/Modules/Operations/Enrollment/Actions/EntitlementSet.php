<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\InvoiceItem;
use Illuminate\Support\Collection;

/** One student's granting lines, loaded once and answered in memory. Built by ResolveEntitlementAction. */
class EntitlementSet
{
    /** @param Collection<int, InvoiceItem> $lines */
    public function __construct(private Collection $lines) {}

    /** A lang key naming why this date cannot be booked, or null when it can. */
    public function check(string $sessionDate): ?string
    {
        if ($this->lines->isEmpty()) {
            return 'flash.enrollmentNoEntitlement';
        }

        if ($this->covering($sessionDate)->isEmpty()) {
            return 'flash.enrollmentEntitlementExpired';
        }

        return $this->pick($sessionDate) === null ? 'flash.enrollmentEntitlementExhausted' : null;
    }

    public function pick(string $sessionDate): ?InvoiceItem
    {
        $covering = $this->covering($sessionDate);

        // Never burn a quota line while an unlimited pass applies; then closest to expiry first.
        return $covering->first(fn (InvoiceItem $item) => $item->isUnlimited())
            ?? $covering->filter(fn (InvoiceItem $item) => $item->sessionsRemaining() > 0)
                ->sortBy(fn (InvoiceItem $item) => $item->valid_until?->toDateString() ?? '9999-12-31')
                ->first();
    }

    /** @return Collection<int, InvoiceItem> */
    private function covering(string $sessionDate): Collection
    {
        return $this->lines->filter(fn (InvoiceItem $item) => $item->coversDate($sessionDate));
    }
}
