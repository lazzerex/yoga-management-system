<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';

defineProps({ data: { type: Object, required: true } });

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);
</script>

<template>
    <div class="ym-row-list">
        <div v-for="(count, status) in data.counts" :key="status" class="ym-row">
            <div class="ym-row-main">
                <p class="ym-row-title">{{ statusLabel(status) }}</p>
            </div>
            <div class="ym-row-aside">
                <span :class="['ym-status-pill', status === 'pending' ? 'ym-status-pill--pending' : '', status === 'approved' ? 'ym-status-pill--started' : '']">
                    {{ count }}
                </span>
            </div>
        </div>
    </div>
    <p class="ym-card-note">
        <Link :href="data.url">{{ $t('dashboard.openLessonPlans') }}</Link>
    </p>
</template>
