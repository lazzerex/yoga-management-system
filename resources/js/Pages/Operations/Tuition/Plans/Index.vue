<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import { formatVnd } from '@/composables/useMoney.js';
import FilterBar from '@/Components/UI/FilterBar.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    plans: Object,
    filters: Object,
    options: Object,
    endpoints: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const pendingDelete = ref(null);

const typeLabel = (type) => t(`operations.tuitionType${type.charAt(0).toUpperCase()}${type.slice(1)}`);

const confirmDelete = () => {
    router.delete(pendingDelete.value.destroyUrl, {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.tuitionPlans') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.tuitionPlans') }}
                    <span class="ym-count">{{ plans.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.tuitionPlansSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link :href="endpoints.invoices" class="ym-btn ym-btn--outline">{{ $t('operations.backToInvoices') }}</Link>
                <Link :href="endpoints.create" class="ym-btn ym-btn--primary">
                    <i class="bi bi-plus-lg" /> {{ $t('operations.createTuitionPlan') }}
                </Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('operations.searchPlans')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.tuitionPlanType') }}</span>
                        <select v-model="filters.type" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allPlanTypes') }}</option>
                            <option v-for="type in options.types" :key="type" :value="type">{{ typeLabel(type) }}</option>
                        </select>
                    </label>
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.status') }}</span>
                        <select v-model="filters.status" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allStatuses') }}</option>
                            <option value="active">{{ $t('operations.statusActive') }}</option>
                            <option value="inactive">{{ $t('operations.statusInactive') }}</option>
                        </select>
                    </label>
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh field="name" :label="$t('operations.tuitionPlanName')" :state="filters" @sort="toggleSort" />
                            <SortTh field="type" :label="$t('operations.tuitionPlanType')" :state="filters" @sort="toggleSort" />
                            <SortTh field="price_amount" :label="$t('operations.amount')" :state="filters" numeric @sort="toggleSort" />
                            <SortTh field="session_count" :label="$t('operations.tuitionPlanSessions')" :state="filters" numeric @sort="toggleSort" />
                            <SortTh field="duration_days" :label="$t('operations.tuitionPlanDuration')" :state="filters" numeric @sort="toggleSort" />
                            <th>{{ $t('operations.planBranch') }}</th>
                            <th>{{ $t('operations.status') }}</th>
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans.data" :key="plan.id">
                            <td class="is-strong">{{ plan.name }}</td>
                            <td class="is-muted">{{ typeLabel(plan.type) }}</td>
                            <td class="is-num is-strong">{{ formatVnd(plan.price_amount) }}</td>
                            <td class="is-num is-muted">{{ plan.session_count ?? '-' }}</td>
                            <td class="is-num is-muted">{{ plan.duration_days ?? '-' }}</td>
                            <td class="is-muted">{{ plan.branch_name ?? $t('operations.allBranches') }}</td>
                            <td>
                                <span class="ym-tag" :class="plan.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                    {{ plan.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="plan.editUrl">{{ $t('operations.edit') }}</Link>
                                    <button
                                        type="button"
                                        class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                        @click="pendingDelete = plan"
                                    >
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!plans.data.length" class="ym-empty">
                <i class="bi bi-tags" />
                <p>{{ $t('operations.noTuitionPlans') }}</p>
            </div>

            <div v-if="plans.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in plans.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal :show="!!pendingDelete" :title="$t('operations.deleteTuitionPlanTitle')" @close="pendingDelete = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteTuitionPlan', { name: pendingDelete?.name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
