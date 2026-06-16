<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const enrolledClasses = [
    { name: 'Evening Yin', coach: 'Ari Gomez', schedule: 'Mon / Wed 18:30', attendance: '10 / 12' },
    { name: 'Power Core', coach: 'Daniel Park', schedule: 'Tue / Thu 07:00', attendance: '8 / 10' },
    { name: 'Mobility Reset', coach: 'Lina Tran', schedule: 'Fri 17:45', attendance: '6 / 8' },
    { name: 'Weekend Flow', coach: 'Mia Chen', schedule: 'Sat 09:00', attendance: '5 / 6' },
    { name: 'Breathwork Lab', coach: 'Noah Brooks', schedule: 'Sun 10:30', attendance: '9 / 10' },
];

const sessionHistory = [
    { id: 1, className: 'Power Core', date: 'Apr 24', coach: 'Daniel Park', result: 'Attended' },
    { id: 2, className: 'Evening Yin', date: 'Apr 22', coach: 'Ari Gomez', result: 'Attended' },
    { id: 3, className: 'Mobility Reset', date: 'Apr 19', coach: 'Lina Tran', result: 'Attended' },
    { id: 4, className: 'Weekend Flow', date: 'Apr 18', coach: 'Mia Chen', result: 'Completed' },
];
</script>

<template>
    <AppLayout title="My Classes">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Active Enrollments</p>
                <p class="ym-stat-value">5</p>
                <p class="ym-stat-note">Across 3 branches</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Sessions Completed</p>
                <p class="ym-stat-value">38</p>
                <p class="ym-stat-note">This membership cycle</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Next Session</p>
                <p class="ym-stat-value">Today 18:30</p>
                <p class="ym-stat-note">Evening Yin · Downtown</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-table ym-pane-icon" />
                        <h2 class="ym-pane-title">Current Enrollments</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">Class</th>
                                    <th class="ym-th">Coach</th>
                                    <th class="ym-th">Schedule</th>
                                    <th class="ym-th">Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in enrolledClasses" :key="item.name" class="ym-tr">
                                    <td class="ym-td font-medium">{{ item.name }}</td>
                                    <td class="ym-td">{{ item.coach }}</td>
                                    <td class="ym-td">{{ item.schedule }}</td>
                                    <td class="ym-td">{{ item.attendance }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-clock-history ym-pane-icon" />
                        <h2 class="ym-pane-title">Recent Session History</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="session in sessionHistory" :key="session.id" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ session.className }}</p>
                                <p class="ym-row-meta">{{ session.date }} · {{ session.coach }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-status-pill ym-status-pill--started">{{ session.result }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>Weekly goal: complete 3 sessions. You are currently at 2 this week.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>