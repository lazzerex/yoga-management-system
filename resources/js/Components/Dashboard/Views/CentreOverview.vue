<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import BarChart from '@/Components/Charts/BarChart.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import { useFilters } from '@/composables/useFilters.js';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({
    data: { type: Object, required: true },
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
    endpoint: { type: String, required: true },
});

const { filters, active, reset } = useFilters(props.endpoint, props.filters, []);

const money = computed(() => props.data.money ?? null);
const activity = computed(() => props.data.activity ?? null);
const attendance = computed(() => props.data.attendance ?? null);
const students = computed(() => props.data.students ?? null);
const today = computed(() => props.data.today ?? []);

const millions = (value) => `${Math.round(value / 1000000)}M`;

// One bar per month, stacked by branch, so the mix is readable as well as the total.
const revenueSeries = computed(() => (money.value?.series ?? []).map((row) => ({
    name: row.branch,
    data: row.amounts,
})));

const revenueTotal = computed(() => money.value?.collectedInWindow ?? 0);
const hasRevenue = computed(() => revenueTotal.value > 0);

const occupancyByBranch = computed(() => activity.value?.byBranch ?? []);
const outstandingByBranch = computed(() => (money.value?.byBranch ?? []).filter((row) => row.outstanding > 0));
const attendanceByBranch = computed(() => (attendance.value?.byBranch ?? []).filter((row) => row.rate > 0));
const studentsByBranch = computed(() => (students.value?.byBranch ?? []).filter((row) => row.students > 0));
const classTypes = computed(() => props.data.byClassType ?? []);
</script>

<template>
    <FilterBar :active="active" @reset="reset">
        <select v-model="filters.branch_id" class="ym-log-filter-select">
            <option value="">{{ $t('dashboard.allBranches') }}</option>
            <option v-for="branch in data.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
        </select>
        <select v-model="filters.months" class="ym-log-filter-select">
            <option v-for="window in options.windows" :key="window" :value="window">
                {{ $t('dashboard.lastMonths', { count: window }) }}
            </option>
        </select>
        <span class="ym-card-note">{{ $t('dashboard.overviewIgnoresSwitcher') }}</span>
    </FilterBar>

    <div class="ym-stat-strip">
        <div v-if="money" class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.collectedThisMonth') }}</p>
            <p class="ym-stat-value">{{ formatVnd(money.collectedThisMonth) }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.inWindow', { amount: formatVnd(money.collectedInWindow) }) }}</p>
        </div>
        <div v-if="money" class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.outstanding') }}</p>
            <p class="ym-stat-value">{{ formatVnd(money.outstanding) }}</p>
            <p class="ym-stat-note">{{ $t('dashboard.overdueCount', { count: money.overdueCount }) }}</p>
        </div>
        <div v-if="activity" class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.seatsFilled') }}</p>
            <p class="ym-stat-value">{{ activity.rate }}%</p>
            <p class="ym-stat-note">
                {{ $t('dashboard.occupancyNote', { booked: activity.booked, capacity: activity.capacity, sessions: activity.sessions }) }}
            </p>
        </div>
        <div v-if="attendance" class="ym-stat">
            <p class="ym-stat-label">{{ $t('dashboard.attendanceRate') }}</p>
            <p class="ym-stat-value">{{ attendance.rate }}%</p>
            <p class="ym-stat-note">{{ $t('dashboard.markedLast30Days', { count: attendance.marked }) }}</p>
        </div>
    </div>

    <div class="ym-analytics-grid mt-3">
        <section v-if="money" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-8">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.revenueByBranch') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!hasRevenue" class="ym-card-note">{{ $t('dashboard.noRevenueYet') }}</p>
                <BarChart
                    v-else
                    stacked
                    :categories="money.months"
                    :series="revenueSeries"
                    :formatter="millions"
                    :tooltip-formatter="formatVnd"
                    :height="280"
                />
            </div>
        </section>

        <section v-if="money" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-4">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.outstandingByBranch') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!outstandingByBranch.length" class="ym-card-note">{{ $t('dashboard.nothingOutstanding') }}</p>
                <DonutChart
                    v-else
                    :labels="outstandingByBranch.map((row) => row.branch)"
                    :series="outstandingByBranch.map((row) => row.outstanding)"
                    :formatter="formatVnd"
                    :height="280"
                />
            </div>
        </section>

        <section v-if="activity" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.occupancyByBranch') }}</h2>
            </header>
            <div class="ym-section">
                <BarChart
                    horizontal
                    :categories="occupancyByBranch.map((row) => row.branch)"
                    :series="[{ name: t('dashboard.fillRate'), data: occupancyByBranch.map((row) => row.rate) }]"
                    :formatter="(value) => `${value}%`"
                    :height="240"
                />
            </div>
        </section>

        <section v-if="attendance" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.attendanceByBranch') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!attendanceByBranch.length" class="ym-card-note">{{ $t('dashboard.noAttendanceMarkedYet') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="attendanceByBranch.map((row) => row.branch)"
                    :series="[{ name: t('dashboard.attendanceRate'), data: attendanceByBranch.map((row) => row.rate) }]"
                    :formatter="(value) => `${value}%`"
                    color="#5f83c2"
                    :height="240"
                />
            </div>
        </section>

        <section v-if="classTypes.length" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.bookingsByClassType') }}</h2>
            </header>
            <div class="ym-section">
                <DonutChart
                    :labels="classTypes.map((row) => row.name)"
                    :series="classTypes.map((row) => row.bookings)"
                    :height="300"
                />
            </div>
        </section>

        <section v-if="students" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-6">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.studentsByBranch') }}</h2>
                <span class="ym-count-badge">{{ students.active }}</span>
            </header>
            <div class="ym-section">
                <p v-if="!studentsByBranch.length" class="ym-card-note">{{ $t('dashboard.noBookingsYet') }}</p>
                <BarChart
                    v-else
                    horizontal
                    :categories="studentsByBranch.map((row) => row.branch)"
                    :series="[{ name: t('dashboard.students'), data: studentsByBranch.map((row) => row.students) }]"
                    color="#8469bf"
                    :height="240"
                />
            </div>
        </section>

        <section v-if="activity" class="ym-surface ym-dashlet-card ym-analytics-card ym-dashlet-span-12">
            <header class="ym-panel-head">
                <h2 class="ym-panel-title">{{ $t('dashboard.todayAcrossTheCentre') }}</h2>
            </header>
            <div class="ym-section">
                <p v-if="!today.length" class="ym-card-note">{{ $t('dashboard.noSessionsToday') }}</p>
                <div v-else class="ym-row-list">
                    <div v-for="session in today" :key="session.id" class="ym-row">
                        <div class="ym-row-main">
                            <p class="ym-row-title">{{ session.start_time }} {{ session.class_type_name }}</p>
                            <p class="ym-row-meta">{{ session.branch_name }} · {{ session.room_name }} · {{ session.coach_name }}</p>
                        </div>
                        <div class="ym-row-aside">
                            <span
                                :class="['ym-status-pill', session.status === 'cancelled' ? 'ym-status-pill--pending' : 'ym-status-pill--started']"
                            >
                                {{ session.status === 'cancelled' ? $t('operations.statusCancelled') : `${session.booked}/${session.capacity}` }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
