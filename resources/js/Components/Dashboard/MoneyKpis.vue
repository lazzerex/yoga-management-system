<script setup>
import { Link } from '@inertiajs/vue3';
import { formatVnd } from '@/composables/useMoney.js';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.collectedThisMonth') }}</p>
            <p class="ym-stat-value">{{ formatVnd(data.collected) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.outstanding') }}</p>
            <p class="ym-stat-value">{{ formatVnd(data.outstanding) }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.openInvoices', { count: data.openCount }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.overdueInvoices') }}</p>
            <p class="ym-stat-value">{{ data.overdueCount }}</p>
            <p class="ym-stat-note">
                <Link :href="data.url">{{ $t('dashboard.openTuitionBoard') }}</Link>
            </p>
        </div>
    </div>
</template>
