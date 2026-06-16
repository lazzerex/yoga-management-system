<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

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

<template>
    <AppLayout title="Lesson Planning & Approval">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Pending Approval</p>
                <p class="ym-stat-value">14</p>
                <p class="ym-stat-note">6 waiting over 48 hours</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Approved This Week</p>
                <p class="ym-stat-value">22</p>
                <p class="ym-stat-note">Avg review time 9h</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Needs Revision</p>
                <p class="ym-stat-value">5</p>
                <p class="ym-stat-note">Feedback sent to teachers</p>
            </div>
        </div>

        <div class="ym-pane">
            <div class="ym-pane-head">
                <div class="ym-pane-title-wrap">
                    <i class="bi bi-clipboard-check ym-pane-icon" />
                    <h2 class="ym-pane-title">Approval Queue</h2>
                </div>
            </div>
            <div class="ym-pane-body">
                <div class="ym-row-list">
                    <div v-for="plan in plans" :key="plan.name" class="ym-row">
                        <div class="ym-row-main">
                            <p class="ym-row-title">{{ plan.name }}</p>
                            <p class="ym-row-meta">{{ plan.teacher }} · {{ plan.branch }} · {{ plan.date }}</p>
                        </div>
                        <div class="ym-row-aside">
                            <span :class="['ym-status-pill', plan.statusClass]">{{ plan.status }}</span>
                            <button type="button" class="ym-btn-outline">Review</button>
                        </div>
                    </div>
                </div>
                <div class="ym-info-row">
                    <i class="bi bi-info-circle ym-info-icon" />
                    <span>Workflow actions and approval permissions are placeholders for now.</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>