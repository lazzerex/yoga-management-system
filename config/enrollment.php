<?php

return [
    // Hours before a session starts after which a member can no longer cancel.
    // No real value was specified by the business yet.
    'cancel_cutoff_hours' => (int) env('ENROLLMENT_CANCEL_CUTOFF_HOURS', 2),

    // Fallback only; the booking.require_entitlement setting overrides it at runtime.
    'require_entitlement' => (bool) env('ENROLLMENT_REQUIRE_ENTITLEMENT', true),
];
