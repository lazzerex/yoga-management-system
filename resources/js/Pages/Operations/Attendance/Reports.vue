<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import SortTh from '@/Components/UI/SortTh.vue';

const props = defineProps({
    month: String,
    taughtHours: Array,
    attendanceRates: Array,
    endpoints: Object,
});

const month = ref(props.month);

watch(month, (value) => {
    if (!value || value === props.month) {
        return;
    }

    router.get(route('operations.attendance.reports'), { month: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const hours = (minutes) => (minutes / 60).toFixed(1);

// Both reports arrive as whole arrays for one month, so they sort in the browser
// rather than costing a round trip.
const makeSort = (defaultField) => {
    const state = ref({ sort: defaultField, dir: 'desc' });

    const toggle = (field) => {
        if (state.value.sort !== field) {
            state.value = { sort: field, dir: 'desc' };
        } else if (state.value.dir === 'desc') {
            state.value = { sort: field, dir: 'asc' };
        } else {
            state.value = { sort: defaultField, dir: 'desc' };
        }
    };

    const apply = (rows) => [...rows].sort((a, b) => {
        const { sort, dir } = state.value;
        const left = a[sort];
        const right = b[sort];
        const order = typeof left === 'string' ? left.localeCompare(right) : left - right;

        return dir === 'asc' ? order : -order;
    });

    return { state, toggle, apply };
};

const { state: hoursSort, toggle: toggleHours, apply: applyHours } = makeSort('minutes');
const { state: ratesSort, toggle: toggleRates, apply: applyRates } = makeSort('rate');

const sortedHours = computed(() => applyHours(props.taughtHours));
const sortedRates = computed(() => applyRates(props.attendanceRates));

const rateTone = (rate) => (rate >= 80 ? 'ok' : rate >= 60 ? 'warn' : 'danger');
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.attendanceReportsTitle') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.attendanceReportsTitle') }}</h1>
                <p class="ym-page-sub">{{ $t('operations.attendanceReportsSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <input v-model="month" type="month" class="ym-log-filter-select" />
                <Link :href="endpoints.board" class="ym-btn ym-btn--outline">
                    {{ $t('operations.attendanceBackToBoard') }}
                </Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-card-head">
                <h2 class="ym-card-title">{{ $t('operations.attendanceTaughtHours') }}</h2>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh
                                field="coach_name"
                                :label="$t('operations.coach')"
                                :state="hoursSort"
                                @sort="toggleHours"
                            />
                            <SortTh
                                field="sessions"
                                :label="$t('operations.attendanceSessionsTaught')"
                                :state="hoursSort"
                                numeric
                                @sort="toggleHours"
                            />
                            <SortTh
                                field="minutes"
                                :label="$t('operations.attendanceHours')"
                                :state="hoursSort"
                                numeric
                                @sort="toggleHours"
                            />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in sortedHours" :key="row.coach_name">
                            <td class="is-strong">{{ row.coach_name }}</td>
                            <td class="is-num">{{ row.sessions }}</td>
                            <td class="is-num is-strong">{{ hours(row.minutes) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!taughtHours.length" class="ym-empty">
                <i class="bi bi-hourglass-split" />
                <p>{{ $t('operations.attendanceNoTaughtHours') }}</p>
            </div>
        </section>

        <section class="ym-card">
            <div class="ym-card-head">
                <h2 class="ym-card-title">{{ $t('operations.attendanceRateTitle') }}</h2>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh
                                field="student_name"
                                :label="$t('operations.student')"
                                :state="ratesSort"
                                @sort="toggleRates"
                            />
                            <SortTh
                                field="total"
                                :label="$t('operations.attendanceMarkedSessions')"
                                :state="ratesSort"
                                numeric
                                @sort="toggleRates"
                            />
                            <th class="is-num">{{ $t('operations.rosterPresent') }}</th>
                            <th class="is-num">{{ $t('operations.rosterLate') }}</th>
                            <th class="is-num">{{ $t('operations.rosterAbsent') }}</th>
                            <SortTh
                                field="rate"
                                :label="$t('operations.attendanceRate')"
                                :state="ratesSort"
                                @sort="toggleRates"
                            />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in sortedRates" :key="row.student_name">
                            <td class="is-strong">{{ row.student_name }}</td>
                            <td class="is-num is-muted">{{ row.total }}</td>
                            <td class="is-num">{{ row.present }}</td>
                            <td class="is-num">{{ row.late }}</td>
                            <td class="is-num">{{ row.absent }}</td>
                            <td>
                                <div class="ym-rate">
                                    <span class="ym-progress">
                                        <span class="ym-progress-fill" :data-tone="rateTone(row.rate)" :style="{ width: `${row.rate}%` }" />
                                    </span>
                                    <span class="ym-rate-value">{{ row.rate }}%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!attendanceRates.length" class="ym-empty">
                <i class="bi bi-person-check" />
                <p>{{ $t('operations.attendanceNoRates') }}</p>
            </div>
        </section>
    </div>
</template>
