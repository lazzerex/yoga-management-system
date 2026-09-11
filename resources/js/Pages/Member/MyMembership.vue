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

const statusTone = {
    paid: 'ok',
    waived: 'neutral',
    partial: 'info',
    unpaid: 'warn',
    overdue: 'danger',
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.myMembership') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('member.activeEntitlements') }}</h1>
                <p class="ym-page-sub">{{ $t('member.activeEntitlementsSubtitle') }}</p>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('member.currentPlan') }}</p>
                <p class="ym-stat-card-value">{{ current?.description ?? $t('member.noActivePlan') }}</p>
                <p v-if="current" class="ym-stat-card-note">{{ $t('member.validUntil', { date: current.valid_until }) }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('member.sessionsIncluded') }}</p>
                <p class="ym-stat-card-value">{{ current ? (current.sessions_granted ?? $t('member.unlimited')) : '—' }}</p>
            </div>
            <div class="ym-stat-card" :class="{ 'ym-stat-card--danger': outstanding > 0 }">
                <p class="ym-stat-card-label">{{ $t('member.outstandingBalance') }}</p>
                <p class="ym-stat-card-value">{{ formatVnd(outstanding) }}</p>
            </div>
        </div>

        <section class="ym-card">
            <ul v-if="entitlements.length" class="ym-timeline">
                <li v-for="entitlement in entitlements" :key="entitlement.id" class="ym-timeline-item">
                    <span class="ym-timeline-mark"><i class="bi bi-patch-check" /></span>
                    <div class="ym-timeline-body">
                        <div class="ym-timeline-row">
                            <span class="ym-timeline-amount">{{ entitlement.description }}</span>
                            <span class="ym-tag ym-tag--ok">{{ $t('operations.invoiceStatusPaid') }}</span>
                        </div>
                        <p class="ym-timeline-meta">
                            {{ entitlement.valid_from ?? '—' }} – {{ entitlement.valid_until }}
                            <template v-if="entitlement.sessions_granted">
                                · {{ $t('member.sessionsGranted', { count: entitlement.sessions_granted }) }}
                            </template>
                        </p>
                    </div>
                </li>
            </ul>

            <div v-else class="ym-empty">
                <i class="bi bi-patch-question" />
                <p>{{ $t('member.noActivePlanNote') }}</p>
            </div>
        </section>

        <section class="ym-card">
            <div class="ym-card-head">
                <h2 class="ym-card-title">{{ $t('member.invoiceHistory') }}</h2>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.invoiceNumber') }}</th>
                            <th>{{ $t('operations.planBranch') }}</th>
                            <th>{{ $t('operations.invoiceIssuedAt') }}</th>
                            <th>{{ $t('operations.dueDate') }}</th>
                            <th class="is-num">{{ $t('operations.amount') }}</th>
                            <th class="is-num">{{ $t('operations.invoiceBalance') }}</th>
                            <th>{{ $t('operations.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in invoices" :key="invoice.id">
                            <td class="is-strong">{{ invoice.invoice_number }}</td>
                            <td class="is-muted">{{ invoice.branch_name }}</td>
                            <td class="is-muted ym-num">{{ invoice.issued_at }}</td>
                            <td class="is-muted ym-num">{{ invoice.due_date }}</td>
                            <td class="is-num">{{ formatVnd(invoice.total_amount) }}</td>
                            <td class="is-num is-strong">{{ formatVnd(invoice.balance) }}</td>
                            <td>
                                <span class="ym-tag" :class="`ym-tag--${statusTone[invoice.status] ?? 'neutral'}`">
                                    {{ statusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td>
                                <a :href="invoice.pdfUrl" class="ym-btn ym-btn--export ym-btn--sm">
                                    <i class="bi bi-filetype-pdf" /> {{ $t('common.exportPdf') }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!invoices.length" class="ym-empty">
                <i class="bi bi-receipt" />
                <p>{{ $t('member.noInvoices') }}</p>
            </div>
        </section>
    </div>
</template>
