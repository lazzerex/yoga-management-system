<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';

const props = defineProps({ data: { type: Object, required: true } });

const growth = computed(() => props.data.growth);
const buckets = computed(() => props.data.attendanceBuckets);
const coaches = computed(() => props.data.coachLoad);
const hasBuckets = computed(() => buckets.value.some((bucket) => bucket.students > 0));
const newestMonth = computed(() => growth.value[growth.value.length - 1]?.count ?? 0);
</script>

<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.activeStudents') }}</p>
            <p class="ym-stat-value">{{ data.activeSplit.active }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.inactiveStudents') }}</p>
            <p class="ym-stat-value">{{ data.activeSplit.inactive }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.newStudentsThisMonth') }}</p>
            <p class="ym-stat-value">{{ newestMonth }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.coachesTeachingThisMonth') }}</p>
            <p class="ym-stat-value">{{ coaches.length }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.studentGrowth') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    :categories="growth.map((month) => month.month)"
                    :series="[{ name: t('dashboard.newStudents'), data: growth.map((month) => month.count) }]"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.studentStatusSplit') }}</h2>
            </header>
            <div class="ym-section">
                <DonutChart
                    :labels="[t('dashboard.active'), t('dashboard.inactive')]"
                    :series="[data.activeSplit.active, data.activeSplit.inactive]"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.attendanceDistribution') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!hasBuckets" class="ym-card-note">{{ $t('dashboard.noAttendanceMarkedYet') }}</p>
                <BarChart
                    v-else
                    :categories="buckets.map((bucket) => bucket.label)"
                    :series="[{ name: t('dashboard.students'), data: buckets.map((bucket) => bucket.students) }]"
                    color="#5f83c2"
                />
            </div>
        </section>

        <section class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.coachLoadThisMonth') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!coaches.length" class="ym-card-note">{{ $t('dashboard.noTeachingRecorded') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="coaches.map((coach) => coach.name)"
                    :series="[{ name: t('dashboard.hours'), data: coaches.map((coach) => Math.round((coach.minutes / 60) * 10) / 10) }]"
                    :formatter="(value) => `${value}h`"
                    color="#8469bf"
                />
            </div>
        </section>
    </div>
</template>
