<template>
    <AppLayout title="Tuition Fee Collection">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Fee Collection Overview</h2>
            <p class="ym-subtitle">
                Billing and tuition monitoring for memberships, class packages, and recurring plans.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Collected This Month</p>
                    <p class="ym-kpi-value">$48,920</p>
                    <p class="ym-kpi-note">82% of monthly target</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Pending Invoices</p>
                    <p class="ym-kpi-value">37</p>
                    <p class="ym-kpi-note">$12,440 outstanding</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Overdue</p>
                    <p class="ym-kpi-value">9</p>
                    <p class="ym-kpi-note">Follow-up campaign needed</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Recent Tuition Records</h3>
                <div class="ym-table-wrap mt-0">
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
            </article>

            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Collection Channels</h3>
                <div class="ym-list">
                    <div class="ym-list-item">
                        <div>
                            <strong>Bank Transfer</strong>
                            <p class="ym-list-meta">Primary method for monthly plans</p>
                        </div>
                        <span class="ym-chip">53%</span>
                    </div>
                    <div class="ym-list-item">
                        <div>
                            <strong>Card Payment</strong>
                            <p class="ym-list-meta">Front desk and online checkout</p>
                        </div>
                        <span class="ym-chip">31%</span>
                    </div>
                    <div class="ym-list-item">
                        <div>
                            <strong>E-Wallet</strong>
                            <p class="ym-list-meta">Preferred by younger members</p>
                        </div>
                        <span class="ym-chip">16%</span>
                    </div>
                </div>

                <p class="ym-note-banner mt-3">
                    <strong>Design Preview:</strong> Payment processing and reminders are not connected yet.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const fees = [
    { student: 'Sophia Nguyen', plan: 'Unlimited Monthly', amount: '$129', dueDate: 'Apr 21', status: 'Paid', statusClass: 'ym-status-pill--started' },
    { student: 'Ethan Cole', plan: '10-Class Pack', amount: '$160', dueDate: 'Apr 22', status: 'Pending', statusClass: 'ym-status-pill--pending' },
    { student: 'Luna Bennett', plan: 'Private 1:1', amount: '$220', dueDate: 'Apr 18', status: 'Overdue', statusClass: 'ym-status-pill--pending' },
    { student: 'Noah Blake', plan: 'Weekend Membership', amount: '$89', dueDate: 'Apr 25', status: 'Scheduled', statusClass: '' },
];
</script>
