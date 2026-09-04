<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <p v-if="!data.count" class="ym-card-note">{{ $t('dashboard.noPlansAwaitingReview') }}</p>

    <template v-else>
        <div class="ym-row-list">
            <Link v-for="plan in data.items" :key="plan.id" :href="plan.showUrl" class="ym-row">
                <div class="ym-row-main">
                    <p class="ym-row-title">{{ plan.title }}</p>
                    <p class="ym-row-meta">{{ plan.coach_name }} · {{ plan.class_type_name }}</p>
                </div>
                <div class="ym-row-aside">
                    <span class="ym-status-pill ym-status-pill--pending">{{ plan.submitted_at }}</span>
                </div>
            </Link>
        </div>
        <p class="ym-card-note">
            <Link :href="data.url">{{ $t('dashboard.reviewQueueCount', { count: data.count }) }}</Link>
        </p>
    </template>
</template>
