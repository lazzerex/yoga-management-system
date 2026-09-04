<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.sessionsToday') }}</p>
            <p class="ym-stat-value">{{ data.sessions }}</p>
            <p v-if="data.students !== undefined" class="ym-stat-note">
                {{ $t('dashboard.studentsBooked', { count: data.students }) }}
            </p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.coachesCheckedIn') }}</p>
            <p class="ym-stat-value">{{ data.checkedIn }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.rostersComplete') }}</p>
            <p class="ym-stat-value">{{ data.rostersComplete }}</p>
            <p class="ym-stat-note">
                <Link :href="data.url">{{ $t('dashboard.openAttendanceBoard') }}</Link>
            </p>
        </div>
    </div>
</template>
