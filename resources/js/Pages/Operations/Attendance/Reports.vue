<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

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
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.attendanceReportsTitle') }, () => page),
};
</script>

<template>
    <div class="ym-at-stack">
        <section class="ym-surface ym-section">
            <div class="ym-at-roster-head">
                <div>
                    <h2 class="ym-title">{{ $t('operations.attendanceReportsTitle') }}</h2>
                    <p class="ym-subtitle">{{ $t('operations.attendanceReportsSubtitle') }}</p>
                </div>
                <div class="ym-at-toolbar">
                    <input v-model="month" type="month" class="ym-log-filter-select" />
                    <Link :href="endpoints.board" class="ym-btn-outline">
                        {{ $t('operations.attendanceBackToBoard') }}
                    </Link>
                </div>
            </div>
        </section>

        <div class="ym-at-reports-grid">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-hourglass-split ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.attendanceTaughtHours') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ $t('operations.coach') }}</th>
                                    <th class="ym-th">{{ $t('operations.attendanceSessionsTaught') }}</th>
                                    <th class="ym-th">{{ $t('operations.attendanceHours') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in taughtHours" :key="row.coach_name" class="ym-tr">
                                    <td class="ym-td font-medium">{{ row.coach_name }}</td>
                                    <td class="ym-td">{{ row.sessions }}</td>
                                    <td class="ym-td">{{ hours(row.minutes) }}</td>
                                </tr>
                                <tr v-if="!taughtHours.length">
                                    <td class="ym-td text-neutral-500" colspan="3">{{ $t('operations.attendanceNoTaughtHours') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-person-check ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.attendanceRateTitle') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ $t('operations.student') }}</th>
                                    <th class="ym-th">{{ $t('operations.attendanceMarkedSessions') }}</th>
                                    <th class="ym-th">{{ $t('operations.rosterPresent') }}</th>
                                    <th class="ym-th">{{ $t('operations.rosterLate') }}</th>
                                    <th class="ym-th">{{ $t('operations.rosterAbsent') }}</th>
                                    <th class="ym-th">{{ $t('operations.attendanceRate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in attendanceRates" :key="row.student_name" class="ym-tr">
                                    <td class="ym-td font-medium">{{ row.student_name }}</td>
                                    <td class="ym-td">{{ row.total }}</td>
                                    <td class="ym-td">{{ row.present }}</td>
                                    <td class="ym-td">{{ row.late }}</td>
                                    <td class="ym-td">{{ row.absent }}</td>
                                    <td class="ym-td">
                                        <div class="ym-at-rate">
                                            <span class="ym-at-rate-bar"><span :style="{ width: `${row.rate}%` }" /></span>
                                            <span>{{ row.rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!attendanceRates.length">
                                    <td class="ym-td text-neutral-500" colspan="6">{{ $t('operations.attendanceNoRates') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
