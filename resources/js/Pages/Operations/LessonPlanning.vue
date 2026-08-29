<script setup>

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
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.lessonPlanning') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.pendingApproval') }}</p>
            <p class="ym-stat-value">14</p>
            <p class="ym-stat-note">6 {{ $t('operations.waitingOver48h') }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.approvedThisWeek') }}</p>
            <p class="ym-stat-value">22</p>
            <p class="ym-stat-note">{{ $t('operations.avgReviewTime') }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.needsRevision') }}</p>
            <p class="ym-stat-value">5</p>
            <p class="ym-stat-note">{{ $t('operations.feedbackSent') }}</p>
        </div>
    </div>

    <div class="ym-pane">
        <div class="ym-pane-head">
            <div class="ym-pane-title-wrap">
                <i class="bi bi-clipboard-check ym-pane-icon" />
                <h2 class="ym-pane-title">{{ $t('operations.approvalQueue') }}</h2>
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
                        <button type="button" class="ym-btn-outline">{{ $t('operations.review') }}</button>
                    </div>
                </div>
            </div>
            <div class="ym-info-row">
                <i class="bi bi-info-circle ym-info-icon" />
                <span>{{ $t('operations.lessonPlaceholder') }}</span>
            </div>
        </div>
    </div>
</template>