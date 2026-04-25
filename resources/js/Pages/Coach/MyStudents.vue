<template>
    <AppLayout title="My Students">
        <section class="ym-surface ym-section ym-reveal">
            <h2 class="ym-title">Student Roster</h2>
            <p class="ym-subtitle">Cross-class visibility of your students, attendance consistency, and support priorities.</p>

            <div class="ym-table-wrap mt-0">
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
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Students Needing Follow-up</h3>
                <div class="ym-list">
                    <div
                        v-for="(alert, index) in followUps"
                        :key="alert.student"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ alert.student }}</strong>
                            <p class="ym-list-meta">{{ alert.note }}</p>
                        </div>
                        <span class="ym-status-pill ym-status-pill--pending">Follow-up</span>
                    </div>
                </div>
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Coaching Snapshot</h3>
                <div class="ym-list">
                    <div v-for="metric in coachingSnapshot" :key="metric.label" class="ym-list-item">
                        <div>
                            <strong>{{ metric.label }}</strong>
                            <p class="ym-list-meta">{{ metric.note }}</p>
                        </div>
                        <span class="ym-chip">{{ metric.value }}</span>
                    </div>
                </div>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

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
