<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';

const props = defineProps({ data: { type: Object, required: true } });

const weekly = computed(() => props.data.weekly);
const byClassType = computed(() => props.data.byClassType);
const rate = computed(() => (props.data.totals.marked > 0
    ? Math.round((props.data.totals.attended / props.data.totals.marked) * 100)
    : 0));

const totals = computed(() => weekly.value.reduce((acc, week) => ({
    present: acc.present + week.present,
    late: acc.late + week.late,
    absent: acc.absent + week.absent,
}), { present: 0, late: 0, absent: 0 }));

const share = (value) => (props.data.totals.marked > 0 ? Math.round((value / props.data.totals.marked) * 100) : 0);

const breakdownSeries = computed(() => [
    { name: t('dashboard.present'), data: weekly.value.map((week) => week.present) },
    { name: t('dashboard.late'), data: weekly.value.map((week) => week.late) },
    { name: t('dashboard.absent'), data: weekly.value.map((week) => week.absent) },
]);
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.attendanceRateWindow', { weeks: data.weeks }) }}</p>
            <p class="ym-stat-value">{{ rate }}%</p>
            <p class="ym-stat-note">{{ $t('dashboard.attendanceNote', { attended: data.totals.attended, total: data.totals.marked }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.present') }}</p>
            <p class="ym-stat-value">{{ share(totals.present) }}%</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.late') }}</p>
            <p class="ym-stat-value">{{ share(totals.late) }}%</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.absent') }}</p>
            <p class="ym-stat-value">{{ share(totals.absent) }}%</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.attendanceRateTrend') }}</h2>
            </header>
            <div class="ym-section">
                <LineChart
                    :categories="weekly.map((week) => week.week)"
                    :series="[{ name: t('dashboard.attendanceRate'), data: weekly.map((week) => week.rate) }]"
                    :formatter="(value) => `${value}%`"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-12">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.attendanceBreakdown') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    stacked
                    :categories="weekly.map((week) => week.week)"
                    :series="breakdownSeries"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.noShowByClassType') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!byClassType.length" class="ym-card-note">{{ $t('dashboard.noAttendanceMarkedYet') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="byClassType.map((type) => type.name)"
                    :series="[{ name: t('dashboard.noShowRate'), data: byClassType.map((type) => type.noShowRate) }]"
                    :formatter="(value) => `${value}%`"
                    color="#c64f57"
                />
            </div>
        </section>
    </div>
</template>
