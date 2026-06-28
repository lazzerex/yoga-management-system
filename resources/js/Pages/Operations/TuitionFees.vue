<script setup>
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const records = [
    { student: 'Alice Chen', plan: 'Unlimited Monthly', amount: '$120', due: 'May 01', status: 'Paid' },
    { student: 'Ben Walker', plan: '10-Class Pass', amount: '$150', due: 'May 05', status: 'Pending' },
    { student: 'Clara Park', plan: 'Unlimited Monthly', amount: '$120', due: 'Apr 28', status: 'Overdue' },
    { student: 'David Tran', plan: 'Drop-In (x5)', amount: '$90', due: 'May 10', status: 'Pending' },
    { student: 'Emma Lee', plan: 'Annual Premium', amount: '$960', due: 'May 03', status: 'Paid' },
];

const channels = [
    { method: 'Bank Transfer', count: 82, pct: '45%' },
    { method: 'Credit Card', count: 58, pct: '32%' },
    { method: 'Cash', count: 27, pct: '15%' },
    { method: 'E-Wallet', count: 15, pct: '8%' },
];
</script>

<template>
    <AppLayout :title="t('operations.tuitionFees')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.collectedThisMonth') }}</p>
                <p class="ym-stat-value">$18,240</p>
                <p class="ym-stat-note">{{ t('operations.ofMonthlyTarget') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.pendingInvoices') }}</p>
                <p class="ym-stat-value">34</p>
                <p class="ym-stat-note">$6,200 {{ t('operations.outstanding') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ t('operations.overdue') }}</p>
                <p class="ym-stat-value">12</p>
                <p class="ym-stat-note">{{ t('operations.followUpNeeded') }}</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-receipt ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('operations.tuitionRecords') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ t('operations.student') }}</th>
                                    <th class="ym-th">{{ t('operations.plan') }}</th>
                                    <th class="ym-th">{{ t('operations.amount') }}</th>
                                    <th class="ym-th">{{ t('operations.dueDate') }}</th>
                                    <th class="ym-th">{{ t('operations.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in records" :key="r.student" class="ym-tr">
                                    <td class="ym-td font-medium">{{ r.student }}</td>
                                    <td class="ym-td">{{ r.plan }}</td>
                                    <td class="ym-td">{{ r.amount }}</td>
                                    <td class="ym-td">{{ r.due }}</td>
                                    <td class="ym-td">
                                        <span class="ym-tag">{{ r.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-credit-card ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ t('operations.collectionChannels') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="ch in channels" :key="ch.method" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ ch.method }}</p>
                                <p class="ym-row-meta">{{ ch.count }} {{ t('operations.thisMonth') }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ ch.pct }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ t('operations.tuitionPlaceholder') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>