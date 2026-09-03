<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import { formatVnd } from '@/composables/useMoney.js';

defineProps({
    invoices: Object,
    stats: Object,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.invoiceStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.tuitionFees') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.collectedThisMonth') }}</p>
            <p class="ym-stat-value">{{ formatVnd(stats.collected) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.outstanding') }}</p>
            <p class="ym-stat-value">{{ formatVnd(stats.outstanding) }}</p>
            <p class="ym-stat-note">{{ $t('operations.openInvoices', { count: stats.openCount }) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.overdue') }}</p>
            <p class="ym-stat-value">{{ stats.overdueCount }}</p>
        </div>
    </div>

    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.tuitionFees') }}
                    <span class="ym-count-badge">{{ invoices.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.tuitionSubtitle') }}</p>
            </div>
            <div class="ym-inline-actions">
                <a :href="endpoints.export" class="ym-btn-outline">{{ $t('common.export') }}</a>
                <Link v-if="endpoints.plans" :href="endpoints.plans" class="ym-btn-outline">
                    {{ $t('operations.tuitionPlans') }}
                </Link>
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn-sm">
                    {{ $t('operations.createInvoice') }}
                </Link>
            </div>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.invoiceNumber') }}</th>
                        <th class="ym-th">{{ $t('operations.student') }}</th>
                        <th class="ym-th">{{ $t('operations.planBranch') }}</th>
                        <th class="ym-th">{{ $t('operations.dueDate') }}</th>
                        <th class="ym-th">{{ $t('operations.amount') }}</th>
                        <th class="ym-th">{{ $t('operations.invoiceBalance') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="invoice in invoices.data" :key="invoice.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ invoice.invoice_number }}</td>
                        <td class="ym-td">{{ invoice.student_name }}</td>
                        <td class="ym-td text-neutral-500">{{ invoice.branch_name }}</td>
                        <td class="ym-td text-neutral-500">{{ invoice.due_date }}</td>
                        <td class="ym-td">{{ formatVnd(invoice.total_amount) }}</td>
                        <td class="ym-td">{{ formatVnd(invoice.balance) }}</td>
                        <td class="ym-td">
                            <span :class="['ym-invoice-status', `ym-invoice-status--${invoice.status}`]">{{ statusLabel(invoice.status) }}</span>
                        </td>
                        <td class="ym-td">
                            <Link class="ym-btn-outline" :href="invoice.showUrl">{{ $t('operations.invoiceDetails') }}</Link>
                        </td>
                    </tr>
                    <tr v-if="!invoices.data.length">
                        <td class="ym-td text-neutral-500" colspan="8">{{ $t('operations.noInvoices') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="invoices.links.length > 3" class="ym-pagination">
            <Link
                v-for="link in invoices.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                preserve-scroll
            />
        </div>
    </section>
</template>
