<template>
    <AppLayout title="Students, Classes & Teachers">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Academic Management</h2>
            <p class="ym-subtitle">
                Design skeleton for managing student profiles, class schedules, and teacher assignments.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Students</p>
                    <p class="ym-kpi-value">486</p>
                    <p class="ym-kpi-note">72 new this quarter</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Class Templates</p>
                    <p class="ym-kpi-value">18</p>
                    <p class="ym-kpi-note">Beginner to advanced</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Teachers</p>
                    <p class="ym-kpi-value">27</p>
                    <p class="ym-kpi-note">21 active this week</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Sample Class Schedule</h3>
                <div class="ym-table-wrap mt-0">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">Class</th>
                                <th class="ym-th">Teacher</th>
                                <th class="ym-th">Branch</th>
                                <th class="ym-th">Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in classes" :key="item.className" class="ym-tr">
                                <td class="ym-td font-medium">{{ item.className }}</td>
                                <td class="ym-td">{{ item.teacher }}</td>
                                <td class="ym-td">{{ item.branch }}</td>
                                <td class="ym-td">{{ item.capacity }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Teacher Assignment Queue</h3>
                <div class="ym-list">
                    <div v-for="assignment in assignments" :key="assignment.title" class="ym-list-item">
                        <div>
                            <strong>{{ assignment.title }}</strong>
                            <p class="ym-list-meta">{{ assignment.meta }}</p>
                        </div>
                        <span class="ym-status-pill">{{ assignment.status }}</span>
                    </div>
                </div>
                <p class="ym-card-note">
                    This section is visual-only for now and will later support assignment actions.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const classes = [
    { className: 'Morning Vinyasa', teacher: 'Mia Tran', branch: 'Downtown', capacity: '22 / 24' },
    { className: 'Prenatal Flow', teacher: 'Emily Rogers', branch: 'Westside', capacity: '12 / 16' },
    { className: 'Power Core', teacher: 'Daniel Park', branch: 'Riverside', capacity: '20 / 20' },
    { className: 'Evening Yin', teacher: 'Ari Gomez', branch: 'Uptown', capacity: '18 / 20' },
];

const assignments = [
    { title: 'Substitute for Evening Yin', meta: 'Uptown / Apr 22', status: 'Pending' },
    { title: 'New Intro to Ashtanga slot', meta: 'Downtown / Apr 25', status: 'Review' },
    { title: 'Weekend workshop staffing', meta: 'Westside / Apr 28', status: 'Planned' },
];
</script>
