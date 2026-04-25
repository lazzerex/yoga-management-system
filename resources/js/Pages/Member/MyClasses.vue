<template>
    <AppLayout title="My Classes">
        <section class="ym-surface ym-section ym-reveal">
            <h2 class="ym-title">Class Enrollment Overview</h2>
            <p class="ym-subtitle">Track your active enrollments, completed sessions, and upcoming class commitments.</p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Active Enrollments</p>
                    <p class="ym-kpi-value">5</p>
                    <p class="ym-kpi-note">Across 3 branches</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Sessions Completed</p>
                    <p class="ym-kpi-value">38</p>
                    <p class="ym-kpi-note">This membership cycle</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Next Session</p>
                    <p class="ym-kpi-value">Today 18:30</p>
                    <p class="ym-kpi-note">Evening Yin · Downtown</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Current Enrollments</h3>
                <div class="ym-table-wrap mt-0">
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
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Recent Session History</h3>
                <div class="ym-list">
                    <div
                        v-for="(session, index) in sessionHistory"
                        :key="session.id"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ session.className }}</strong>
                            <p class="ym-list-meta">{{ session.date }} · {{ session.coach }}</p>
                        </div>
                        <span class="ym-status-pill">{{ session.result }}</span>
                    </div>
                </div>
                <p class="ym-note-banner mt-3">
                    Weekly goal: complete 3 sessions. You are currently at 2 this week.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

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
