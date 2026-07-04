<script setup>
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const enrolledClasses = [
    { name: 'Evening Yin', coach: 'Ari Gomez', schedule: 'Mon / Wed 18:30', attendance: '10 / 12' },
    { name: 'Power Core', coach: 'Daniel Park', schedule: 'Tue / Thu 07:00', attendance: '8 / 10' },
    { name: 'Mobility Reset', coach: 'Lina Tran', schedule: 'Fri 17:45', attendance: '6 / 8' },
    { name: 'Weekend Flow', coach: 'Mia Chen', schedule: 'Sat 09:00', attendance: '5 / 6' },
    { name: 'Breathwork Lab', coach: 'Noah Brooks', schedule: 'Sun 10:30', attendance: '9 / 10' },
];

const sessionHistory = [
    { id: 1, className: 'Power Core', date: 'Apr 24', coach: 'Daniel Park', result: t('member.sessionAttended') },
    { id: 2, className: 'Evening Yin', date: 'Apr 22', coach: 'Ari Gomez', result: t('member.sessionAttended') },
    { id: 3, className: 'Mobility Reset', date: 'Apr 19', coach: 'Lina Tran', result: t('member.sessionAttended') },
    { id: 4, className: 'Weekend Flow', date: 'Apr 18', coach: 'Mia Chen', result: t('member.sessionCompleted') },
];
</script>

<template>
    <AppLayout :title="$t('member.myClassesMember')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('member.enrolled') }}</p>
                <p class="ym-stat-value">5</p>
                <p class="ym-stat-note">{{ $t('member.acrossBranchesCount', { count: 3 }) }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('member.schedule') }}</p>
                <p class="ym-stat-value">38</p>
                <p class="ym-stat-note">{{ $t('member.unlimitedOrQuantity', { value: 'This membership cycle' }) }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('member.mySchedule') }}</p>
                <p class="ym-stat-value">{{ $t('dashboard.today1830') }}</p>
                <p class="ym-stat-note">{{ $t('member.todayClassMeta') }}</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-table ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.enrolled') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ $t('operations.class') }}</th>
                                    <th class="ym-th">{{ $t('operations.teacher') }}</th>
                                    <th class="ym-th">{{ $t('member.schedule') }}</th>
                                    <th class="ym-th">{{ $t('operations.attendance') }}</th>
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
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-clock-history ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.schedule') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="session in sessionHistory" :key="session.id" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ session.className }}</p>
                                <p class="ym-row-meta">{{ session.date }} · {{ session.coach }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-status-pill ym-status-pill--started">{{ session.result }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ $t('member.weeklyGoalPlaceholder', { count: 3, current: 2 }) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>