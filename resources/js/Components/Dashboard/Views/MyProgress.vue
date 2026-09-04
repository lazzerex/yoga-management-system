<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';

const props = defineProps({ data: { type: Object, required: true } });

const weekly = computed(() => props.data.weekly);
const split = computed(() => props.data.split.filter((entry) => entry.count > 0));
const rate = computed(() => (props.data.totals.marked > 0
    ? Math.round((props.data.totals.attended / props.data.totals.marked) * 100)
    : 0));

const missed = computed(() => props.data.totals.marked - props.data.totals.attended);

const statusLabel = (status) => t(`dashboard.${status}`);
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.attendanceRateWindow', { weeks: data.weeks }) }}</p>
            <p class="ym-stat-value">{{ rate }}%</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.sessionsAttended') }}</p>
            <p class="ym-stat-value">{{ data.totals.attended }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.attendanceNote', { attended: data.totals.attended, total: data.totals.marked }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.sessionsMissed') }}</p>
            <p class="ym-stat-value">{{ missed }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.sessionsPerWeek') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    stacked
                    :categories="weekly.map((week) => week.week)"
                    :series="[
                        { name: t('dashboard.attended'), data: weekly.map((week) => week.attended) },
                        { name: t('dashboard.absent'), data: weekly.map((week) => week.absent) },
                    ]"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.myAttendanceSplit') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!split.length" class="ym-card-note">{{ $t('dashboard.noAttendanceMarkedYet') }}</p>
                <DonutChart
                    v-else
                    :labels="split.map((entry) => statusLabel(entry.status))"
                    :series="split.map((entry) => entry.count)"
                />
            </div>
        </section>
    </div>
</template>
