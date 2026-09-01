<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    sessions: Array,
    filters: Object,
    stats: Object,
    endpoints: Object,
});

const date = ref(props.filters.date);
const busyId = ref(null);

watch(date, (value) => {
    if (!value || value === props.filters.date) {
        return;
    }

    router.get(route('operations.teacher-attendance'), { date: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

// Local parts, not toISOString(): UTC conversion lands on the wrong day east of GMT.
const ymd = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const shiftDate = (days) => {
    const next = new Date(`${date.value}T00:00:00`);
    next.setDate(next.getDate() + days);
    date.value = ymd(next);
};

const goToToday = () => {
    date.value = ymd(new Date());
};

const locale = () => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US');

const longDate = computed(() =>
    new Intl.DateTimeFormat(locale(), { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(`${date.value}T00:00:00`)),
);

const clockTime = (value) =>
    value ? new Intl.DateTimeFormat(locale(), { hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : null;

const shiftState = (row) => {
    if (row.checked_out_at) return 'done';
    if (row.checked_in_at) return 'onDuty';
    return 'notStarted';
};

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
    <div class="ym-at-stack">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.attendanceSessionsToday') }}</p>
                <p class="ym-stat-value">{{ stats.sessions }}</p>
                <p class="ym-stat-note">{{ longDate }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.attendanceCheckedInCount') }}</p>
                <p class="ym-stat-value">{{ stats.checkedIn }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.attendanceRostersComplete') }}</p>
                <p class="ym-stat-value">{{ stats.rostersComplete }}</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-clipboard-check ym-pane-icon" />
                    <div>
                        <h2 class="ym-pane-title">{{ $t('operations.todayAttendance') }}</h2>
                        <p class="ym-subtitle">{{ $t('operations.attendanceBoardSubtitle') }}</p>
                    </div>
                </div>
                <div class="ym-at-toolbar">
                    <button type="button" class="ym-btn-icon-btn" :title="$t('operations.attendancePrevDay')" @click="shiftDate(-1)">
                        <i class="bi bi-chevron-left" />
                    </button>
                    <input v-model="date" type="date" class="ym-log-filter-select" />
                    <button type="button" class="ym-btn-icon-btn" :title="$t('operations.attendanceNextDay')" @click="shiftDate(1)">
                        <i class="bi bi-chevron-right" />
                    </button>
                    <button type="button" class="ym-btn-outline" @click="goToToday">
                        {{ $t('operations.attendanceToday') }}
                    </button>
                    <Link :href="endpoints.reports" class="ym-btn-outline">
                        <i class="bi bi-bar-chart ym-btn-icon" />
                        {{ $t('operations.attendanceReports') }}
                    </Link>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-table-wrap">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">{{ $t('operations.time') }}</th>
                                <th class="ym-th">{{ $t('operations.class') }}</th>
                                <th class="ym-th">{{ $t('operations.teacher') }}</th>
                                <th class="ym-th">{{ $t('operations.room') }}</th>
                                <th class="ym-th">{{ $t('operations.checkIn') }}</th>
                                <th class="ym-th">{{ $t('operations.attendance') }}</th>
                                <th class="ym-th">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in sessions" :key="row.id" class="ym-tr">
                                <td class="ym-td font-medium">{{ row.start_time }} - {{ row.end_time }}</td>
                                <td class="ym-td">{{ row.class_type_name }}</td>
                                <td class="ym-td">{{ row.coach_name }}</td>
                                <td class="ym-td text-neutral-500">{{ row.branch_name }} / {{ row.room_name }}</td>
                                <td class="ym-td">
                                    <span
                                        class="ym-status-pill"
                                        :class="{
                                            'ym-status-pill--started': shiftState(row) === 'onDuty',
                                            'ym-status-pill--booked': shiftState(row) === 'done',
                                        }"
                                    >
                                        {{
                                            shiftState(row) === 'notStarted'
                                                ? $t('operations.attendanceNotStarted')
                                                : shiftState(row) === 'onDuty'
                                                  ? $t('operations.attendanceOnDuty')
                                                  : $t('operations.attendanceShiftDone')
                                        }}
                                    </span>
                                    <span v-if="row.checked_in_at" class="ym-at-clock">
                                        {{ clockTime(row.checked_in_at) }}<template v-if="row.checked_out_at"> - {{ clockTime(row.checked_out_at) }}</template>
                                    </span>
                                </td>
                                <td class="ym-td text-neutral-500">
                                    {{ $t('operations.attendanceProgress', { marked: row.marked_count, total: row.booked_count }) }}
                                </td>
                                <td class="ym-td">
                                    <div class="ym-at-actions">
                                        <button
                                            v-if="row.checkInUrl"
                                            type="button"
                                            class="ym-btn-primary ym-btn-sm"
                                            :disabled="busyId === row.id"
                                            @click="submit(row.checkInUrl, row.id)"
                                        >
                                            {{ $t('operations.checkIn') }}
                                        </button>
                                        <button
                                            v-if="row.checkOutUrl"
                                            type="button"
                                            class="ym-btn-outline ym-btn-sm"
                                            :disabled="busyId === row.id"
                                            @click="submit(row.checkOutUrl, row.id)"
                                        >
                                            {{ $t('operations.attendanceCheckOut') }}
                                        </button>
                                        <Link :href="row.rosterUrl" class="ym-btn-ghost ym-btn-sm">
                                            {{ $t('operations.attendanceRoster') }}
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!sessions.length">
                                <td class="ym-td text-neutral-500" colspan="7">{{ $t('operations.attendanceNoSessions') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
