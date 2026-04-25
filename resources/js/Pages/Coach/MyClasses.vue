<template>
    <AppLayout title="My Classes">
        <section class="ym-surface ym-section ym-reveal">
            <h2 class="ym-title">Teaching Classes Overview</h2>
            <p class="ym-subtitle">A live snapshot of the classes you lead, attendance momentum, and class load distribution.</p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Classes This Week</p>
                    <p class="ym-kpi-value">14</p>
                    <p class="ym-kpi-note">Across 3 branches</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Total Students</p>
                    <p class="ym-kpi-value">182</p>
                    <p class="ym-kpi-note">Active class participants</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Average Fill Rate</p>
                    <p class="ym-kpi-value">87%</p>
                    <p class="ym-kpi-note">+4% from last week</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-1">
                <h3 class="ym-subsection-title">Class Roster Summary</h3>
                <div class="ym-table-wrap mt-0">
                    <table class="ym-table">
                        <thead>
                            <tr>
                                <th class="ym-th">Class</th>
                                <th class="ym-th">Branch</th>
                                <th class="ym-th">Students</th>
                                <th class="ym-th">Waitlist</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in classes" :key="item.name" class="ym-tr">
                                <td class="ym-td font-medium">{{ item.name }}</td>
                                <td class="ym-td">{{ item.branch }}</td>
                                <td class="ym-td">{{ item.students }}</td>
                                <td class="ym-td">{{ item.waitlist }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="ym-surface ym-section ym-reveal ym-reveal-delay-2">
                <h3 class="ym-subsection-title">Class Insights</h3>
                <div class="ym-list">
                    <div
                        v-for="(insight, index) in insights"
                        :key="insight.title"
                        class="ym-list-item ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <div>
                            <strong>{{ insight.title }}</strong>
                            <p class="ym-list-meta">{{ insight.meta }}</p>
                        </div>
                        <span class="ym-tag">{{ insight.label }}</span>
                    </div>
                </div>
                <p class="ym-note-banner mt-3">
                    Static preview data for now. Live attendance and class health scoring will be wired in a later phase.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const classes = [
    { name: 'Power Core', branch: 'Riverside', students: 24, waitlist: 3 },
    { name: 'Evening Yin', branch: 'Downtown', students: 21, waitlist: 1 },
    { name: 'Prenatal Flow', branch: 'Westside', students: 14, waitlist: 0 },
    { name: 'Weekend Flow', branch: 'Uptown', students: 19, waitlist: 2 },
    { name: 'Breathwork Lab', branch: 'Online', students: 32, waitlist: 5 },
];

const insights = [
    { title: 'Power Core is near full capacity', meta: 'Riverside · Tue/Thu 07:00', label: 'High Demand' },
    { title: 'Prenatal Flow attendance improving', meta: 'Westside · +11% this month', label: 'Trend Up' },
    { title: 'Weekend Flow has repeat bookings', meta: 'Uptown · retention 82%', label: 'Strong' },
];
</script>
