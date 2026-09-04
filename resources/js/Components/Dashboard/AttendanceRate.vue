<script setup>
import { trans as t } from 'laravel-vue-i18n';
import RadialChart from '@/Components/Charts/RadialChart.vue';

defineProps({ data: { type: Object, required: true } });
</script>

<template>
    <p v-if="!data.total" class="ym-card-note">{{ $t('dashboard.noAttendanceMarkedYet') }}</p>

    <template v-else>
        <RadialChart :value="data.rate" :label="t('dashboard.attended')" color="#5f83c2" />
        <p class="ym-card-note">{{ $t('dashboard.attendanceNote', { attended: data.attended, total: data.total }) }}</p>
    </template>
</template>
