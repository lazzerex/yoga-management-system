<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** The Monday of the week a ?week= query names, falling back to this week on anything unparsable. */
class Week
{
    public static function start(?string $date): CarbonImmutable
    {
        try {
            $day = $date ? CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay() : null;
        } catch (\Throwable) {
            $day = null;
        }

        return ($day ?? CarbonImmutable::today())->startOfWeek(CarbonImmutable::MONDAY);
    }
}
