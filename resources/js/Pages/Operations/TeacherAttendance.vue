<template>
    <AppLayout title="Teacher Attendance Tracking">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Attendance Snapshot</h2>
            <p class="ym-subtitle">
                Mocked attendance board for instructor check-ins, lateness tracking, and branch-level visibility.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">On Time</p>
                    <p class="ym-kpi-value">21</p>
                    <p class="ym-kpi-note">77% attendance quality</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Late</p>
                    <p class="ym-kpi-value">4</p>
                    <p class="ym-kpi-note">Avg delay 8 minutes</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Absent</p>
                    <p class="ym-kpi-value">2</p>
                    <p class="ym-kpi-note">All covered by substitutes</p>
                </article>
            </div>
        </section>

        <section class="ym-surface ym-section mt-4">
            <h3 class="ym-subsection-title">Today Attendance Board</h3>
            <div class="ym-table-wrap mt-0">
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

            <p class="ym-note-banner">
                <strong>Design Preview:</strong> Attendance actions and geolocation validation are planned but not enabled yet.
            </p>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const attendance = [
    { teacher: 'Mia Tran', branch: 'Downtown', shift: '06:00 - 12:00', checkIn: '05:51', status: 'On Time' },
    { teacher: 'Daniel Park', branch: 'Riverside', shift: '07:00 - 13:00', checkIn: '07:08', status: 'Late' },
    { teacher: 'Ari Gomez', branch: 'Uptown', shift: '10:00 - 16:00', checkIn: '09:53', status: 'On Time' },
    { teacher: 'Emily Rogers', branch: 'Westside', shift: '14:00 - 20:00', checkIn: '13:58', status: 'On Time' },
    { teacher: 'Noah Blake', branch: 'Downtown', shift: '16:00 - 22:00', checkIn: '--', status: 'Absent' },
];
</script>
