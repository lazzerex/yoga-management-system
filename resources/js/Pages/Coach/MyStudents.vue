<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faChartSimple, faTriangleExclamation, faUsers } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '@/Layouts/AppLayout.vue';

const students = [
    { name: 'Nora Ellis', primaryClass: 'Power Core', attendance: '9 / 10', lastSession: 'Apr 24' },
    { name: 'Kevin Yu', primaryClass: 'Evening Yin', attendance: '8 / 10', lastSession: 'Apr 22' },
    { name: 'Ava Sato', primaryClass: 'Prenatal Flow', attendance: '7 / 8', lastSession: 'Apr 23' },
    { name: 'Mason Reed', primaryClass: 'Weekend Flow', attendance: '5 / 8', lastSession: 'Apr 18' },
    { name: 'Sienna Park', primaryClass: 'Breathwork Lab', attendance: '10 / 10', lastSession: 'Apr 25' },
    { name: 'Noel Grant', primaryClass: 'Power Core', attendance: '4 / 8', lastSession: 'Apr 11' },
];

const followUps = [
    { student: 'Mason Reed', note: 'Attendance down for 2 consecutive weeks' },
    { student: 'Noel Grant', note: 'Requested support for shoulder mobility modifications' },
    { student: 'Kevin Yu', note: 'Interested in progressing to intermediate sequence' },
];

const coachingSnapshot = [
    { label: 'Average Attendance', note: 'All classes this month', value: '84%' },
    { label: 'Students at Risk', note: 'Attendance < 60%', value: '6' },
    { label: 'High Consistency Students', note: 'Attendance > 90%', value: '23' },
];
</script>

<template>
    <AppLayout title="My Students">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Total Students</p>
                <p class="ym-stat-value">182</p>
                <p class="ym-stat-note">Across all active classes</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Avg Attendance</p>
                <p class="ym-stat-value">84%</p>
                <p class="ym-stat-note">All classes this month</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Need Follow-up</p>
                <p class="ym-stat-value">3</p>
                <p class="ym-stat-note">Attendance or support flags</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <FontAwesomeIcon :icon="faUsers" class="ym-pane-icon" />
                    <h2 class="ym-pane-title">Student Roster</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-table-wrap">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">Student</th>
                                <th class="ym-th">Primary Class</th>
                                <th class="ym-th">Attendance</th>
                                <th class="ym-th">Last Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="student.name" class="ym-tr">
                                <td class="ym-td font-medium">{{ student.name }}</td>
                                <td class="ym-td">{{ student.primaryClass }}</td>
                                <td class="ym-td">{{ student.attendance }}</td>
                                <td class="ym-td">{{ student.lastSession }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Students Needing Follow-up</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="alert in followUps" :key="alert.student" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ alert.student }}</p>
                                <p class="ym-row-meta">{{ alert.note }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-status-pill ym-status-pill--pending">Follow-up</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faChartSimple" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Coaching Snapshot</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="metric in coachingSnapshot" :key="metric.label" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ metric.label }}</p>
                                <p class="ym-row-meta">{{ metric.note }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ metric.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>