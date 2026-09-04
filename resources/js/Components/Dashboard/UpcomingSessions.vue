<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <p v-if="!data.count" class="ym-card-note">{{ $t('dashboard.noBookingsYet') }}</p>

    <template v-else>
        <div class="ym-row-list">
            <div v-for="booking in data.items" :key="booking.id" class="ym-row">
                <div class="ym-row-main">
                    <p class="ym-row-title">{{ booking.class_type_name }}</p>
                    <p class="ym-row-meta">{{ booking.branch_name }} · {{ booking.room_name }}</p>
                </div>
                <div class="ym-row-aside">
                    <span class="ym-status-pill">{{ booking.session_date }} {{ booking.start_time }}</span>
                </div>
            </div>
        </div>
        <p class="ym-card-note">
            <Link :href="data.url">{{ $t('dashboard.bookedSessionsCount', { count: data.count }) }}</Link>
        </p>
    </template>
</template>
