<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const attendance = [
    { teacher: 'Mia Tran', branch: 'Downtown', shift: '06:00 - 12:00', checkIn: '05:51', status: 'On Time' },
    { teacher: 'Daniel Park', branch: 'Riverside', shift: '07:00 - 13:00', checkIn: '07:08', status: 'Late' },
    { teacher: 'Ari Gomez', branch: 'Uptown', shift: '10:00 - 16:00', checkIn: '09:53', status: 'On Time' },
    { teacher: 'Emily Rogers', branch: 'Westside', shift: '14:00 - 20:00', checkIn: '13:58', status: 'On Time' },
    { teacher: 'Noah Blake', branch: 'Downtown', shift: '16:00 - 22:00', checkIn: '--', status: 'Absent' },
];
</script>

<template>
    <AppLayout :title="$t('operations.teacherAttendance')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.onTime') }}</p>
                <p class="ym-stat-value">21</p>
                <p class="ym-stat-note">77% {{ $t('operations.attendanceQuality') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.late') }}</p>
                <p class="ym-stat-value">4</p>
                <p class="ym-stat-note">{{ $t('operations.avgDelay') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.absent') }}</p>
                <p class="ym-stat-value">2</p>
                <p class="ym-stat-note">{{ $t('operations.coveredBySubs') }}</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-clipboard-check ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ $t('operations.todayAttendance') }}</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-table-wrap">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">{{ $t('operations.teacher') }}</th>
                                <th class="ym-th">{{ $t('operations.branch') }}</th>
                                <th class="ym-th">{{ $t('operations.shift') }}</th>
                                <th class="ym-th">{{ $t('operations.checkIn') }}</th>
                                <th class="ym-th">{{ $t('operations.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in attendance" :key="entry.teacher" class="ym-tr">
                                <td class="ym-td font-medium">{{ entry.teacher }}</td>
                                <td class="ym-td">{{ entry.branch }}</td>
                                <td class="ym-td">{{ entry.shift }}</td>
                                <td class="ym-td">{{ entry.checkIn }}</td>
                                <td class="ym-td">
                                    <span
                                        :class="[
                                            'ym-status-pill',
                                            entry.status === 'Late' ? 'ym-status-pill--pending' : '',
                                            entry.status === 'On Time' ? 'ym-status-pill--started' : '',
                                        ]"
                                    >
                                        {{ entry.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="ym-info-row">
                    <i class="bi bi-info-circle ym-info-icon" />
                    <span>{{ $t('operations.attendancePlaceholder') }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>