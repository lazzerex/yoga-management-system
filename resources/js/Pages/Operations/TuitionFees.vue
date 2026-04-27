<template>
    <AppLayout title="Tuition Fee Collection">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Collected This Month</p>
                <p class="ym-stat-value">$48,920</p>
                <p class="ym-stat-note">82% of monthly target</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Pending Invoices</p>
                <p class="ym-stat-value">37</p>
                <p class="ym-stat-note">$12,440 outstanding</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Overdue</p>
                <p class="ym-stat-value">9</p>
                <p class="ym-stat-note">Follow-up campaign needed</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <FontAwesomeIcon :icon="faMoneyBillWave" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Recent Tuition Records</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">Student</th>
                                    <th class="ym-th">Plan</th>
                                    <th class="ym-th">Amount</th>
                                    <th class="ym-th">Due Date</th>
                                    <th class="ym-th">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="fee in fees" :key="fee.student" class="ym-tr">
                                    <td class="ym-td font-medium">{{ fee.student }}</td>
                                    <td class="ym-td">{{ fee.plan }}</td>
                                    <td class="ym-td">{{ fee.amount }}</td>
                                    <td class="ym-td">{{ fee.dueDate }}</td>
                                    <td class="ym-td">
                                        <span :class="['ym-status-pill', fee.statusClass]">{{ fee.status }}</span>
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
                        <FontAwesomeIcon :icon="faChartSimple" class="ym-pane-icon" />
                        <h2 class="ym-pane-title">Collection Channels</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="channel in channels" :key="channel.name" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ channel.name }}</p>
                                <p class="ym-row-meta">{{ channel.meta }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ channel.share }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <FontAwesomeIcon :icon="faCircleInfo" class="ym-info-icon" />
                        <span>Payment processing and reminders are not connected yet.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faChartSimple, faCircleInfo, faMoneyBillWave } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';

const fees = [
    { student: 'Sophia Nguyen', plan: 'Unlimited Monthly', amount: '$129', dueDate: 'Apr 21', status: 'Paid', statusClass: 'ym-status-pill--started' },
    { student: 'Ethan Cole', plan: '10-Class Pack', amount: '$160', dueDate: 'Apr 22', status: 'Pending', statusClass: 'ym-status-pill--pending' },
    { student: 'Luna Bennett', plan: 'Private 1:1', amount: '$220', dueDate: 'Apr 18', status: 'Overdue', statusClass: 'ym-status-pill--pending' },
    { student: 'Noah Blake', plan: 'Weekend Membership', amount: '$89', dueDate: 'Apr 25', status: 'Scheduled', statusClass: '' },
];

const channels = [
    { name: 'Bank Transfer', meta: 'Primary method for monthly plans', share: '53%' },
    { name: 'Card Payment', meta: 'Front desk and online checkout', share: '31%' },
    { name: 'E-Wallet', meta: 'Preferred by younger members', share: '16%' },
];
</script>
