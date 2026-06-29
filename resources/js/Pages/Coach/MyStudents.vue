<script setup>
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
</script>

<template>
    <AppLayout :title="$t('coach.myStudents', { count: 182 })">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('coach.totalStudents') }}</p>
                <p class="ym-stat-value">182</p>
                <p class="ym-stat-note">{{ $t('coach.acrossAllActiveClasses') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('coach.avgAttendance') }}</p>
                <p class="ym-stat-value">84%</p>
                <p class="ym-stat-note">{{ $t('coach.allClassesThisMonth') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('coach.needFollowUp') }}</p>
                <p class="ym-stat-value">3</p>
                <p class="ym-stat-note">{{ $t('coach.attendanceOrSupportFlags') }}</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-people ym-pane-icon" />
                    <h2 class="ym-pane-title">{{ $t('coach.studentRoster') }}</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-table-wrap">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">{{ $t('auth.name') }}</th>
                                <th class="ym-th">{{ $t('coach.primaryClass') }}</th>
                                <th class="ym-th">{{ $t('coach.avgAttendance') }}</th>
                                <th class="ym-th">{{ $t('coach.lastSession') }}</th>
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
                        <i class="bi bi-exclamation-triangle ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('coach.studentsNeedingFollowup') }}</h2>
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
                                <span class="ym-status-pill ym-status-pill--pending">{{ $t('coach.followUp') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-bar-chart ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('coach.coachingSnapshot') }}</h2>
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