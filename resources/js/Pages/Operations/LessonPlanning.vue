<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import FilterBar from '@/Components/UI/FilterBar.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    plans: Object,
    stats: Object,
    filters: Object,
    options: Object,
    canManage: Boolean,
    endpoints: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const levelLabel = (level) => t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`);

const statusTone = {
    draft: 'neutral',
    pending: 'warn',
    approved: 'ok',
    rejected: 'danger',
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.lessonPlans') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.statusDraft') }}</p>
                <p class="ym-stat-card-value">{{ stats.draft }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--warn">
                <p class="ym-stat-card-label">{{ $t('operations.statusPending') }}</p>
                <p class="ym-stat-card-value">{{ stats.pending }}</p>
            </div>
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.statusApproved') }}</p>
                <p class="ym-stat-card-value">{{ stats.approved }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--danger">
                <p class="ym-stat-card-label">{{ $t('operations.statusRejected') }}</p>
                <p class="ym-stat-card-value">{{ stats.rejected }}</p>
            </div>
        </div>

        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.lessonPlans') }}
                    <span class="ym-count">{{ plans.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.lessonPlansSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link v-if="endpoints.pending" :href="endpoints.pending" class="ym-btn ym-btn--outline">
                    {{ $t('operations.approvalQueue') }}
                </Link>
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn ym-btn--primary">
                    <i class="bi bi-plus-lg" /> {{ $t('operations.createLessonPlan') }}
                </Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('operations.searchTitle')"
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
                        <span>{{ $t('operations.planClassType') }}</span>
                        <select v-model="filters.class_type_id" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allClassTypes') }}</option>
                            <option v-for="type in options.classTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </label>
                    <label v-if="options.coaches.length" class="ym-filter-field">
                        <span>{{ $t('operations.planCoach') }}</span>
                        <select v-model="filters.coach_profile_id" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allCoaches') }}</option>
                            <option v-for="coach in options.coaches" :key="coach.id" :value="coach.id">{{ coach.name }}</option>
                        </select>
                    </label>
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh field="title" :label="$t('operations.planTitle')" :state="filters" @sort="toggleSort" />
                            <th>{{ $t('operations.planClassType') }}</th>
                            <th>{{ $t('operations.planCoach') }}</th>
                            <th>{{ $t('operations.planBranch') }}</th>
                            <SortTh field="level" :label="$t('operations.planLevel')" :state="filters" @sort="toggleSort" />
                            <SortTh field="status" :label="$t('operations.status')" :state="filters" @sort="toggleSort" />
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans.data" :key="plan.id">
                            <td class="is-strong">{{ plan.title }}</td>
                            <td class="is-muted">{{ plan.class_type_name }}</td>
                            <td class="is-muted">{{ plan.coach_name }}</td>
                            <td class="is-muted">{{ plan.branch_name }}</td>
                            <td class="is-muted">{{ levelLabel(plan.level) }}</td>
                            <td>
                                <span class="ym-tag" :class="`ym-tag--${statusTone[plan.status] ?? 'neutral'}`">
                                    {{ statusLabel(plan.status) }}
                                </span>
                            </td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="plan.showUrl">
                                        {{ $t('operations.planDetails') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!plans.data.length" class="ym-empty">
                <i class="bi bi-journal-text" />
                <p>{{ $t('operations.noLessonPlans') }}</p>
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
    </div>
</template>
