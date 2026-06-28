<script setup>
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const classes = [
    { name: 'Power Core', branch: 'Riverside', students: 24, waitlist: 3 },
    { name: 'Evening Yin', branch: 'Downtown', students: 21, waitlist: 1 },
    { name: 'Prenatal Flow', branch: 'Westside', students: 14, waitlist: 0 },
    { name: 'Weekend Flow', branch: 'Uptown', students: 19, waitlist: 2 },
    { name: 'Breathwork Lab', branch: 'Online', students: 32, waitlist: 5 },
];

const insights = [
    { title: 'Power Core is near full capacity', meta: 'Riverside · Tue/Thu 07:00', label: t('coach.highDemand') },
    { title: 'Prenatal Flow attendance improving', meta: 'Westside · +11% this month', label: t('coach.trendUp') },
    { title: 'Weekend Flow has repeat bookings', meta: 'Uptown · retention 82%', label: t('coach.strong') },
];
</script>

<template>
    <AppLayout :title="t('coach.myClasses')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('coach.classesThisWeek') }}</p>
                <p class="ym-stat-value">14</p>
                <p class="ym-stat-note">{{ t('coach.acrossBranches', { count: 3 }) }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('coach.totalStudents') }}</p>
                <p class="ym-stat-value">182</p>
                <p class="ym-stat-note">{{ t('coach.activeParticipants') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('coach.avgFillRate') }}</p>
                <p class="ym-stat-value">87%</p>
                <p class="ym-stat-note">{{ t('coach.vsLastWeek', { change: '+4%' }) }}</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-easel ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('coach.classRosterSummary') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ t('operations.class') }}</th>
                                    <th class="ym-th">{{ t('operations.branch') }}</th>
                                    <th class="ym-th">{{ t('operations.students') }}</th>
                                    <th class="ym-th">{{ t('coach.waitlist') }}</th>
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
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-lightbulb ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('coach.classInsights') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="insight in insights" :key="insight.title" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ insight.title }}</p>
                                <p class="ym-row-meta">{{ insight.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ insight.label }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>Live attendance and class health scoring will be wired in a later phase.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>