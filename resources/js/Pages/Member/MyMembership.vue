<script setup>
import { trans as t } from 'laravel-vue-i18n';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({
    entitlements: Array,
    invoices: Array,
    outstanding: Number,
});

const statusLabel = (status) => t(`operations.invoiceStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const current = props.entitlements[0] ?? null;
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.myMembership') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.currentPlan') }}</p>
            <p class="ym-stat-value">{{ current?.description ?? $t('member.noActivePlan') }}</p>
            <p v-if="current" class="ym-stat-note">{{ $t('member.validUntil', { date: current.valid_until }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.sessionsIncluded') }}</p>
            <p class="ym-stat-value">{{ current ? (current.sessions_granted ?? $t('member.unlimited')) : '-' }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('member.outstandingBalance') }}</p>
            <p class="ym-stat-value">{{ formatVnd(outstanding) }}</p>
        </div>
    </div>

    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('member.activeEntitlements') }}</h2>
        <p class="ym-subtitle">{{ $t('member.activeEntitlementsSubtitle') }}</p>

        <ul v-if="entitlements.length" class="ym-review-list">
            <li v-for="entitlement in entitlements" :key="entitlement.id" class="ym-review-item">
                <span class="ym-invoice-status ym-invoice-status--paid">{{ $t('operations.invoiceStatusPaid') }}</span>
                <div class="ym-review-body">
                    <p class="font-medium">{{ entitlement.description }}</p>
                    <p class="ym-review-meta">
                        {{ entitlement.valid_from ?? '-' }} - {{ entitlement.valid_until }}
                        <template v-if="entitlement.sessions_granted">
                            · {{ $t('member.sessionsGranted', { count: entitlement.sessions_granted }) }}
                        </template>
                    </p>
                </div>
            </li>
        </ul>
        <p v-else class="ym-card-note">{{ $t('member.noActivePlanNote') }}</p>
    </section>

    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('member.invoiceHistory') }}</h2>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.invoiceNumber') }}</th>
                        <th class="ym-th">{{ $t('operations.planBranch') }}</th>
                        <th class="ym-th">{{ $t('operations.invoiceIssuedAt') }}</th>
                        <th class="ym-th">{{ $t('operations.dueDate') }}</th>
                        <th class="ym-th">{{ $t('operations.amount') }}</th>
                        <th class="ym-th">{{ $t('operations.invoiceBalance') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="invoice in invoices" :key="invoice.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ invoice.invoice_number }}</td>
                        <td class="ym-td text-neutral-500">{{ invoice.branch_name }}</td>
                        <td class="ym-td text-neutral-500">{{ invoice.issued_at }}</td>
                        <td class="ym-td text-neutral-500">{{ invoice.due_date }}</td>
                        <td class="ym-td">{{ formatVnd(invoice.total_amount) }}</td>
                        <td class="ym-td">{{ formatVnd(invoice.balance) }}</td>
                        <td class="ym-td">
                            <span :class="['ym-invoice-status', `ym-invoice-status--${invoice.status}`]">{{ statusLabel(invoice.status) }}</span>
                        </td>
                    </tr>
                    <tr v-if="!invoices.length">
                        <td class="ym-td text-neutral-500" colspan="7">{{ $t('member.noInvoices') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
