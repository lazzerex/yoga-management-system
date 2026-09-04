<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import HeatmapChart from '@/Components/Charts/HeatmapChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';

const props = defineProps({ data: { type: Object, required: true } });

const dayKeys = [
    'dashboard.sundayShort',
    'dashboard.mondayShort',
    'dashboard.tuesdayShort',
    'dashboard.wednesdayShort',
    'dashboard.thursdayShort',
    'dashboard.fridayShort',
    'dashboard.saturdayShort',
];

const hours = computed(() => {
    const found = new Set();
    Object.values(props.data.heatmap).forEach((day) => Object.keys(day).forEach((hour) => found.add(hour)));

    return [...found].sort();
});

// Apex draws the first series at the bottom, so the week reads Monday down to Sunday.
const heatmapSeries = computed(() => [6, 5, 4, 3, 2, 1, 0].map((weekday) => ({
    name: t(dayKeys[weekday]),
    data: hours.value.map((hour) => ({ x: hour, y: props.data.heatmap[weekday]?.[hour]?.rate ?? 0 })),
})));

const hasHeatmap = computed(() => hours.value.length > 0);

const classTypes = computed(() => props.data.byClassType);
const rooms = computed(() => props.data.topRooms);

const trendSeries = computed(() => [
    { name: t('dashboard.booked'), data: props.data.trend.map((week) => week.booked) },
    { name: t('dashboard.capacity'), data: props.data.trend.map((week) => week.capacity) },
]);

const cancellationRate = computed(() => (props.data.total > 0
    ? Math.round((props.data.cancelled / props.data.total) * 100)
    : 0));

const seatsFilled = computed(() => {
    const capacity = props.data.trend.reduce((sum, week) => sum + week.capacity, 0);
    const booked = props.data.trend.reduce((sum, week) => sum + week.booked, 0);

    return capacity > 0 ? Math.round((booked / capacity) * 100) : 0;
});
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.sessionsInWindow', { weeks: data.weeks }) }}</p>
            <p class="ym-stat-value">{{ data.total }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.cancellationRate') }}</p>
            <p class="ym-stat-value">{{ cancellationRate }}%</p>
            <p class="ym-stat-note">{{ $t('dashboard.cancelledCount', { count: data.cancelled }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.seatsFilled') }}</p>
            <p class="ym-stat-value">{{ seatsFilled }}%</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.roomsInUse') }}</p>
            <p class="ym-stat-value">{{ rooms.length }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.occupancyHeatmap') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!hasHeatmap" class="ym-card-note">{{ $t('dashboard.noSessionsInWindow') }}</p>
                <HeatmapChart v-else :series="heatmapSeries" />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.sessionsByClassType') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!classTypes.length" class="ym-card-note">{{ $t('dashboard.noSessionsInWindow') }}</p>
                <DonutChart
                    v-else
                    :labels="classTypes.map((type) => type.name)"
                    :series="classTypes.map((type) => type.sessions)"
                    :height="300"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.bookedVsCapacity') }}</h2>
            </header>
            <div class="ym-section">
                <LineChart :categories="data.trend.map((week) => week.week)" :series="trendSeries" />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.busiestRooms') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!rooms.length" class="ym-card-note">{{ $t('dashboard.noSessionsInWindow') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="rooms.map((room) => room.name)"
                    :series="[{ name: t('dashboard.booked'), data: rooms.map((room) => room.booked) }]"
                    :height="240"
                    color="#5f83c2"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-12">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.fillRateByClassType') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!classTypes.length" class="ym-card-note">{{ $t('dashboard.noSessionsInWindow') }}</p>
                <BarChart
                    v-else
                    :categories="classTypes.map((type) => type.name)"
                    :series="[{ name: t('dashboard.fillRate'), data: classTypes.map((type) => type.rate) }]"
                    :formatter="(value) => `${value}%`"
                />
            </div>
        </section>
    </div>
</template>
