<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';

const props = defineProps({ data: { type: Object, required: true } });

const weekly = computed(() => props.data.teaching.weekly);
const attendance = computed(() => props.data.attendance);
const totalHours = computed(() => Math.round((props.data.teaching.totalMinutes / 60) * 10) / 10);
const sessionsTaught = computed(() => weekly.value.reduce((sum, week) => sum + week.sessions, 0));
const attendanceRate = computed(() => (attendance.value.totals.marked > 0
    ? Math.round((attendance.value.totals.attended / attendance.value.totals.marked) * 100)
    : 0));
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.hoursTaughtWindow', { weeks: data.teaching.weeks }) }}</p>
            <p class="ym-stat-value">{{ totalHours }}h</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.sessionsTaught') }}</p>
            <p class="ym-stat-value">{{ sessionsTaught }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.attendanceRate') }}</p>
            <p class="ym-stat-value">{{ attendanceRate }}%</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.markedSessions') }}</p>
            <p class="ym-stat-value">{{ attendance.totals.marked }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.attendanceNote', { attended: attendance.totals.attended, total: attendance.totals.marked }) }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.hoursPerWeek') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    :categories="weekly.map((week) => week.week)"
                    :series="[{ name: t('dashboard.hours'), data: weekly.map((week) => Math.round((week.minutes / 60) * 10) / 10) }]"
                    :formatter="(value) => `${value}h`"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.myStudentsAttendance') }}</h2>
            </header>
            <div class="ym-section">
                <LineChart
                    :categories="attendance.weekly.map((week) => week.week)"
                    :series="[{ name: t('dashboard.attendanceRate'), data: attendance.weekly.map((week) => week.rate) }]"
                    :formatter="(value) => `${value}%`"
                />
            </div>
        </section>
    </div>
</template>
