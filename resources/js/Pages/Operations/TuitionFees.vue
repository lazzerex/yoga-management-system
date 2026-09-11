<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import { formatVnd } from '@/composables/useMoney.js';
import FilterBar from '@/Components/UI/FilterBar.vue';
import DateRange from '@/Components/UI/DateRange.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
    options: Object,
    endpoints: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const statusLabel = (status) => t(`operations.invoiceStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);

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
    layout: (h, page) => h(AppLayout, { title: t('operations.tuitionFees') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <p class="ym-page-sub">
                    {{ $t('operations.tuitionSubtitle') }}
                    <span class="ym-count">{{ invoices.total }}</span>
                </p>
            </div>
            <div class="ym-page-actions">
                <a :href="endpoints.export" class="ym-btn ym-btn--export">
                    <i class="bi bi-download" /> {{ $t('common.export') }}
                </a>
                <Link v-if="endpoints.plans" :href="endpoints.plans" class="ym-btn ym-btn--outline">
                    {{ $t('operations.tuitionPlans') }}
                </Link>
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn ym-btn--primary">
                    <i class="bi bi-plus-lg" /> {{ $t('operations.createInvoice') }}
                </Link>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.collectedThisMonth') }}</p>
                <p class="ym-stat-card-value">{{ formatVnd(stats.collected) }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('operations.outstanding') }}</p>
                <p class="ym-stat-card-value">{{ formatVnd(stats.outstanding) }}</p>
                <p class="ym-stat-card-note">{{ $t('operations.openInvoices', { count: stats.openCount }) }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--danger">
                <p class="ym-stat-card-label">{{ $t('operations.overdue') }}</p>
                <p class="ym-stat-card-value">{{ stats.overdueCount }}</p>
            </div>
        </div>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('operations.searchInvoices')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.status') }}</span>
                        <select v-model="filters.status" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allStatuses') }}</option>
                            <option v-for="status in options.statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                        </select>
                    </label>
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.overdue') }}</span>
                        <select v-model="filters.overdue" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allInvoices') }}</option>
                            <option value="1">{{ $t('operations.overdueOnly') }}</option>
                        </select>
                    </label>
                    <DateRange
                        v-model:from="filters.from"
                        v-model:to="filters.to"
                        :label="$t('operations.dueDate')"
                    />
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh
                                field="invoice_number"
                                :label="$t('operations.invoiceNumber')"
                                :state="filters"
                                @sort="toggleSort"
                            />
                            <th>{{ $t('operations.student') }}</th>
                            <th>{{ $t('operations.planBranch') }}</th>
                            <SortTh
                                field="due_date"
                                :label="$t('operations.dueDate')"
                                :state="filters"
                                @sort="toggleSort"
                            />
                            <SortTh
                                field="total_amount"
                                :label="$t('operations.amount')"
                                :state="filters"
                                numeric
                                @sort="toggleSort"
                            />
                            <th class="is-num">{{ $t('operations.invoiceBalance') }}</th>
                            <SortTh
                                field="status"
                                :label="$t('operations.status')"
                                :state="filters"
                                @sort="toggleSort"
                            />
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="is-strong">{{ invoice.invoice_number }}</td>
                            <td>{{ invoice.student_name }}</td>
                            <td class="is-muted">{{ invoice.branch_name }}</td>
                            <td class="is-muted ym-num">{{ invoice.due_date }}</td>
                            <td class="is-num">{{ formatVnd(invoice.total_amount) }}</td>
                            <td class="is-num is-strong">{{ formatVnd(invoice.balance) }}</td>
                            <td>
                                <span class="ym-tag" :class="`ym-tag--${statusTone[invoice.status] ?? 'neutral'}`">
                                    {{ statusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="invoice.showUrl">
                                        {{ $t('operations.invoiceDetails') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!invoices.data.length" class="ym-empty">
                <i class="bi bi-receipt" />
                <p>{{ $t('operations.noInvoices') }}</p>
            </div>

            <div v-if="invoices.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in invoices.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>
    </div>
</template>
