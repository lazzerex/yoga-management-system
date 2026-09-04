<script setup>
import { trans as t } from 'laravel-vue-i18n';
import RadialChart from '@/Components/Charts/RadialChart.vue';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <p v-if="!data.sessions" class="ym-card-note">{{ $t('dashboard.noUpcomingSessions') }}</p>

    <template v-else>
        <RadialChart :value="data.rate" :label="t('dashboard.seatsFilled')" />
        <p class="ym-card-note">
            {{ $t('dashboard.occupancyNote', { booked: data.booked, capacity: data.capacity, sessions: data.sessions }) }}
        </p>
    </template>
</template>
