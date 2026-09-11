<script setup>
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import FilterBar from '@/Components/UI/FilterBar.vue';
import DateRange from '@/Components/UI/DateRange.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import SubTabs from '@/Components/UI/SubTabs.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    logs: Object,
    filters: Object,
    endpoints: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.self, props.filters);



const statusBadgeClass = (status) =>
    status === 'failed' ? 'ym-action-badge--login-failed' : 'ym-action-badge--login-success';

const failureReasonLabel = (reason) => {
    if (!reason) return t('admin.unknown');
    const label = t(`admin.loginFailureReasons.${reason}`);
    return label === `admin.loginFailureReasons.${reason}` ? reason : label;
};

const statusLabel = (log) => {
    if (log.status === 'failed') {
        return `${t('admin.failed')} · ${failureReasonLabel(log.failure_reason)}`;
    }
    return t('admin.success');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('admin.loginLogs') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('admin.loginActivity') }}</h1>
                <p class="ym-page-sub">{{ $t('admin.recentSignIns') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link :href="endpoints.users" class="ym-btn ym-btn--quiet">{{ $t('admin.users') }}</Link>
                <a :href="endpoints.export" class="ym-btn ym-btn--outline">
                    <i class="bi bi-download" /> {{ $t('admin.exportCsv') }}
                </a>
            </div>
        </header>

        <SubTabs
            group="admin-logs"
            :tabs="[
                { label: $t('admin.loginLogs'), href: endpoints.self, active: true },
                { label: $t('admin.auditLogs'), href: endpoints.audit_logs, active: false },
            ]"
        />

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('admin.searchIp')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('admin.status') }}</span>
                        <select v-model="filters.status" class="ym-log-filter-select">
                            <option value="">{{ $t('admin.allStatus') }}</option>
                            <option value="success">{{ $t('admin.success') }}</option>
                            <option value="failed">{{ $t('admin.failed') }}</option>
                        </select>
                    </label>
                    <label class="ym-filter-field">
                        <span>{{ $t('admin.device') }}</span>
                        <select v-model="filters.device" class="ym-log-filter-select">
                            <option value="">{{ $t('admin.allDevices') }}</option>
                            <option value="desktop">{{ $t('profile.desktop') }}</option>
                            <option value="mobile">{{ $t('profile.mobile') }}</option>
                        </select>
                    </label>
                    <DateRange v-model:from="filters.from" v-model:to="filters.to" :label="$t('admin.time')" />
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh field="status" :label="$t('admin.status')" :state="filters" @sort="toggleSort" />
                            <th>{{ $t('admin.user') }}</th>
                            <th>{{ $t('admin.identifier') }}</th>
                            <th>{{ $t('admin.ipAddress') }}</th>
                            <th>{{ $t('admin.device') }}</th>
                            <SortTh field="logged_in_at" :label="$t('admin.time')" :state="filters" @sort="toggleSort" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td>
                                <span :class="['ym-action-badge', statusBadgeClass(log.status)]">
                                    {{ statusLabel(log) }}
                                </span>
                            </td>
                            <td class="is-strong">{{ log.user?.name ?? '—' }}</td>
                            <td class="is-muted">{{ log.attempted_identifier ? `@${log.attempted_identifier}` : '—' }}</td>
                            <td class="ym-num">{{ log.ip_address }}</td>
                            <td>
                                <span class="ym-tag ym-tag--neutral">
                                    <i :class="log.device_type === 'mobile' ? 'bi bi-phone' : 'bi bi-laptop'" />
                                    {{ log.device_type }}
                                </span>
                            </td>
                            <td class="is-muted ym-num">{{ formatDate(log.logged_in_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!logs.data.length" class="ym-empty">
                <i class="bi bi-box-arrow-in-right" />
                <p>{{ $t('admin.noLogs') }}</p>
            </div>

            <div v-if="logs.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in logs.links"
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
