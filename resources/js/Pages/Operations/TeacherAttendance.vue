<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';
import FilterBar from '@/Components/UI/FilterBar.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    sessions: Array,
    filters: Object,
    options: Object,
    stats: Object,
    endpoints: Object,
});

// The date is part of the filter set, so moving day keeps the coach and status choices.
const { filters, active, filterCount, reset } = useFilters(props.endpoints.index, props.filters, []);
const busyId = ref(null);

// Local parts, not toISOString(): UTC conversion lands on the wrong day east of GMT.
const ymd = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const shiftDate = (days) => {
    const next = new Date(`${filters.value.date}T00:00:00`);
    next.setDate(next.getDate() + days);
    filters.value.date = ymd(next);
};

const goToToday = () => {
    filters.value.date = ymd(new Date());
};

const locale = () => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US');

const longDate = computed(() =>
    new Intl.DateTimeFormat(locale(), { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${filters.value.date}T00:00:00`)),
);

const clockTime = (value) =>
    value ? new Intl.DateTimeFormat(locale(), { hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : null;

const shiftState = (row) => {
    if (row.checked_out_at) return 'done';
    if (row.checked_in_at) return 'onDuty';
    return 'notStarted';
};

const shiftTone = { notStarted: 'neutral', onDuty: 'info', done: 'ok' };

const submit = (url, id) => {
    busyId.value = id;
    router.post(url, {}, {
        preserveScroll: true,
        onFinish: () => (busyId.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.teacherAttendance') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.todayAttendance') }}</h1>
                <p class="ym-page-sub">{{ $t('operations.attendanceBoardSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <div class="ym-daynav">
                    <button
                        type="button"
                        class="ym-btn ym-btn--outline ym-btn--icon"
                        :title="$t('operations.attendancePrevDay')"
                        @click="shiftDate(-1)"
                    >
                        <i class="bi bi-chevron-left" />
                    </button>
                    <input v-model="filters.date" type="date" class="ym-log-filter-select" />
                    <button
                        type="button"
                        class="ym-btn ym-btn--outline ym-btn--icon"
                        :title="$t('operations.attendanceNextDay')"
                        @click="shiftDate(1)"
                    >
                        <i class="bi bi-chevron-right" />
                    </button>
                </div>
                <button type="button" class="ym-btn ym-btn--outline" @click="goToToday">
                    <i class="bi bi-calendar-event" /> {{ $t('operations.attendanceToday') }}
                </button>
                <Link :href="endpoints.reports" class="ym-btn ym-btn--outline">
                    <i class="bi bi-bar-chart" /> {{ $t('operations.attendanceReports') }}
                </Link>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.attendanceSessionsToday') }}</p>
                <p class="ym-stat-card-value">{{ stats.sessions }}</p>
                <p class="ym-stat-card-note">{{ longDate }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('operations.attendanceCheckedInCount') }}</p>
                <p class="ym-stat-card-value">{{ stats.checkedIn }}</p>
            </div>
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.attendanceRostersComplete') }}</p>
                <p class="ym-stat-card-value">{{ stats.rostersComplete }}</p>
            </div>
        </div>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar :count="filterCount" :active="active" @reset="reset">
                    <label v-if="options.coaches.length" class="ym-filter-field">
                        <span>{{ $t('operations.coach') }}</span>
                        <select v-model="filters.coach_profile_id" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allCoaches') }}</option>
                            <option v-for="coach in options.coaches" :key="coach.id" :value="coach.id">{{ coach.name }}</option>
                        </select>
                    </label>
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.status') }}</span>
                        <select v-model="filters.status" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allStatuses') }}</option>
                            <option value="scheduled">{{ $t('operations.statusScheduled') }}</option>
                            <option value="cancelled">{{ $t('operations.statusCancelled') }}</option>
                            <option value="done">{{ $t('operations.statusDone') }}</option>
                        </select>
                    </label>
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.time') }}</th>
                            <th>{{ $t('operations.class') }}</th>
                            <th>{{ $t('operations.teacher') }}</th>
                            <th>{{ $t('operations.room') }}</th>
                            <th>{{ $t('operations.checkIn') }}</th>
                            <th>{{ $t('operations.attendance') }}</th>
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in sessions" :key="row.id">
                            <td class="is-strong ym-num">{{ row.start_time }} – {{ row.end_time }}</td>
                            <td>{{ row.class_type_name }}</td>
                            <td>{{ row.coach_name }}</td>
                            <td class="is-muted">{{ row.branch_name }} / {{ row.room_name }}</td>
                            <td>
                                <span class="ym-tag" :class="`ym-tag--${shiftTone[shiftState(row)]}`">
                                    {{
                                        shiftState(row) === 'notStarted'
                                            ? $t('operations.attendanceNotStarted')
                                            : shiftState(row) === 'onDuty'
                                              ? $t('operations.attendanceOnDuty')
                                              : $t('operations.attendanceShiftDone')
                                    }}
                                </span>
                                <span v-if="row.checked_in_at" class="ym-timeline-meta ym-num">
                                    {{ clockTime(row.checked_in_at) }}<template v-if="row.checked_out_at"> – {{ clockTime(row.checked_out_at) }}</template>
                                </span>
                            </td>
                            <td class="is-muted ym-num">
                                {{ $t('operations.attendanceProgress', { marked: row.marked_count, total: row.booked_count }) }}
                            </td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <button
                                        v-if="row.checkInUrl"
                                        type="button"
                                        class="ym-btn ym-btn--primary ym-btn--sm"
                                        :disabled="busyId === row.id"
                                        @click="submit(row.checkInUrl, row.id)"
                                    >
                                        {{ $t('operations.checkIn') }}
                                    </button>
                                    <button
                                        v-if="row.checkOutUrl"
                                        type="button"
                                        class="ym-btn ym-btn--outline ym-btn--sm"
                                        :disabled="busyId === row.id"
                                        @click="submit(row.checkOutUrl, row.id)"
                                    >
                                        {{ $t('operations.attendanceCheckOut') }}
                                    </button>
                                    <Link :href="row.rosterUrl" class="ym-btn ym-btn--quiet ym-btn--sm">
                                        {{ $t('operations.attendanceRoster') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!sessions.length" class="ym-empty">
                <i class="bi bi-clipboard-check" />
                <p>{{ $t('operations.attendanceNoSessions') }}</p>
            </div>
        </section>
    </div>
</template>
