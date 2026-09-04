<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.newStudentsThisMonth') }}</p>
            <p class="ym-stat-value">{{ data.count }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.lastMonthCount', { count: data.previous }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.activeStudents') }}</p>
            <p class="ym-stat-value">{{ data.total }}</p>
            <p class="ym-stat-note">
                <Link :href="data.url">{{ $t('dashboard.openStudentDirectory') }}</Link>
            </p>
        </div>
    </div>
</template>
