<script setup>
import { Link } from '@inertiajs/vue3';
import { formatVnd } from '@/composables/useMoney.js';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.outstandingBalance') }}</p>
            <p class="ym-stat-value">{{ formatVnd(data.outstanding) }}</p>
            <p class="ym-stat-note">
                <Link :href="data.url">{{ $t('dashboard.openMyMembership') }}</Link>
            </p>
        </div>
    </div>

    <p v-if="!data.entitlements.length" class="ym-card-note">{{ $t('dashboard.noActiveEntitlements') }}</p>

    <div v-else class="ym-row-list">
        <div v-for="entitlement in data.entitlements" :key="entitlement.id" class="ym-row">
            <div class="ym-row-main">
                <p class="ym-row-title">{{ entitlement.description }}</p>
                <p class="ym-row-meta">{{ $t('dashboard.validUntil', { date: entitlement.valid_until }) }}</p>
            </div>
            <div class="ym-row-aside">
                <span v-if="entitlement.sessions_granted" class="ym-status-pill ym-status-pill--started">
                    {{ $t('dashboard.sessionsGranted', { count: entitlement.sessions_granted }) }}
                </span>
            </div>
        </div>
    </div>
</template>
