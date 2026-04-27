<template>
    <AppLayout title="Teacher Attendance Tracking">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">On Time</p>
                <p class="ym-stat-value">21</p>
                <p class="ym-stat-note">77% attendance quality</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Late</p>
                <p class="ym-stat-value">4</p>
                <p class="ym-stat-note">Avg delay 8 minutes</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Absent</p>
                <p class="ym-stat-value">2</p>
                <p class="ym-stat-note">All covered by substitutes</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <FontAwesomeIcon :icon="faClipboardCheck" class="ym-pane-icon" />
                    <h2 class="ym-pane-title">Today Attendance Board</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-table-wrap">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">Teacher</th>
                                <th class="ym-th">Branch</th>
                                <th class="ym-th">Shift</th>
                                <th class="ym-th">Check In</th>
                                <th class="ym-th">Status</th>
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
                    <FontAwesomeIcon :icon="faCircleInfo" class="ym-info-icon" />
                    <span>Attendance actions and geolocation validation are planned but not enabled yet.</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCircleInfo, faClipboardCheck } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';

const attendance = [
    { teacher: 'Mia Tran', branch: 'Downtown', shift: '06:00 - 12:00', checkIn: '05:51', status: 'On Time' },
    { teacher: 'Daniel Park', branch: 'Riverside', shift: '07:00 - 13:00', checkIn: '07:08', status: 'Late' },
    { teacher: 'Ari Gomez', branch: 'Uptown', shift: '10:00 - 16:00', checkIn: '09:53', status: 'On Time' },
    { teacher: 'Emily Rogers', branch: 'Westside', shift: '14:00 - 20:00', checkIn: '13:58', status: 'On Time' },
    { teacher: 'Noah Blake', branch: 'Downtown', shift: '16:00 - 22:00', checkIn: '--', status: 'Absent' },
];
</script>
