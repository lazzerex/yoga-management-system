<script setup>
defineProps({ data: { type: Array, required: true } });
</script>

<template>
    <p v-if="!data.length" class="ym-card-note">{{ $t('dashboard.noSessionsToday') }}</p>

    <div v-else class="ym-row-list">
        <div v-for="session in data" :key="session.id" class="ym-row">
            <div class="ym-row-main">
                <p class="ym-row-title">{{ session.start_time }}–{{ session.end_time }} {{ session.class_type_name }}</p>
                <p class="ym-row-meta">{{ session.coach_name }} · {{ session.room_name }}</p>
            </div>
            <div class="ym-row-aside">
                <span :class="['ym-status-pill', session.status === 'cancelled' ? 'ym-status-pill--pending' : 'ym-status-pill--started']">
                    {{ session.booked }}/{{ session.capacity }}
                </span>
            </div>
        </div>
    </div>
</template>
