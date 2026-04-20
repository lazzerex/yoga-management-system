<template>
    <AppLayout title="Lesson Planning & Approval">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Lesson Planning Pipeline</h2>
            <p class="ym-subtitle">
                Draft lesson plans submitted by teachers and queued for management approval.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Pending Approval</p>
                    <p class="ym-kpi-value">14</p>
                    <p class="ym-kpi-note">6 waiting over 48 hours</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Approved This Week</p>
                    <p class="ym-kpi-value">22</p>
                    <p class="ym-kpi-note">Avg review time 9h</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Needs Revision</p>
                    <p class="ym-kpi-value">5</p>
                    <p class="ym-kpi-note">Feedback sent to teachers</p>
                </article>
            </div>
        </section>

        <section class="ym-surface ym-section mt-4">
            <h3 class="ym-subsection-title">Approval Queue</h3>

            <div class="ym-list">
                <div v-for="plan in plans" :key="plan.name" class="ym-list-item">
                    <div>
                        <strong>{{ plan.name }}</strong>
                        <p class="ym-list-meta">{{ plan.teacher }} / {{ plan.branch }} / {{ plan.date }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span :class="['ym-status-pill', plan.statusClass]">{{ plan.status }}</span>
                        <button type="button" class="ym-btn-outline">Review</button>
                    </div>
                </div>
            </div>

            <p class="ym-note-banner">
                <strong>Design Preview:</strong> Workflow actions and approval permissions are placeholders for now.
            </p>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const plans = [
    {
        name: 'Hip Mobility - Intermediate Flow',
        teacher: 'Mia Tran',
        branch: 'Downtown',
        date: 'Apr 22',
        status: 'Pending',
        statusClass: 'ym-status-pill--pending',
    },
    {
        name: 'Breathwork Foundations',
        teacher: 'Emily Rogers',
        branch: 'Westside',
        date: 'Apr 23',
        status: 'Approved',
        statusClass: 'ym-status-pill--started',
    },
    {
        name: 'Kids Yoga Focus Session',
        teacher: 'Ari Gomez',
        branch: 'Uptown',
        date: 'Apr 24',
        status: 'Revision',
        statusClass: '',
    },
    {
        name: 'Yin Recovery for Athletes',
        teacher: 'Daniel Park',
        branch: 'Riverside',
        date: 'Apr 25',
        status: 'Pending',
        statusClass: 'ym-status-pill--pending',
    },
];
</script>
