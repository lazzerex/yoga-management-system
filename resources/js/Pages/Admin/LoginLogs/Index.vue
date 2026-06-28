<script setup>
import { useI18n } from 'vue-i18n';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({
    logs: Object,
    filters: Object,
    endpoints: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const device = ref(props.filters?.device ?? '');
const sortDir = ref(props.filters?.sort_dir ?? 'desc');

const hasActiveFilters = computed(() => search.value || status.value || device.value);

let searchTimeout = null;

function applyFilters() {
    router.get(props.endpoints.self, {
        search: search.value || undefined,
        status: status.value || undefined,
        device: device.value || undefined,
        sort_dir: sortDir.value === 'desc' ? undefined : sortDir.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

watch([status, device, sortDir], applyFilters);

function toggleSort() {
    sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc';
}

function resetFilters() {
    router.get(props.endpoints.self, {}, { preserveState: false, replace: true });
}

const FAILURE_REASON_LABELS = {
    wrong_password: t('admin.wrongPassword'),
    user_not_found: t('admin.userNotFound'),
};

const statusBadgeClass = (status) =>
    status === 'failed' ? 'ym-action-badge--login-failed' : 'ym-action-badge--login-success';

const statusLabel = (log) => {
    if (log.status === 'failed') {
        return `${t('admin.failed')} · ${FAILURE_REASON_LABELS[log.failure_reason] ?? log.failure_reason ?? t('admin.unknown')}`;
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

<template>
    <AppLayout :title="t('admin.loginLogs')">
        <section class="ym-surface ym-section">
            <div class="ym-log-page-head">
                <div>
                    <h2 class="ym-title">{{ t('admin.loginActivity') }}</h2>
                    <p class="ym-subtitle">{{ t('admin.recentSignIns') }}</p>
                </div>
                <div class="ym-log-head-actions">
                    <a :href="endpoints.export" class="ym-btn-outline">{{ t('admin.exportCsv') }}</a>
                    <Link :href="endpoints.users" class="ym-btn-ghost">{{ t('admin.users') }}</Link>
                </div>
            </div>

            <div class="ym-log-tabs">
                <Link :href="endpoints.self" class="ym-log-tab ym-log-tab--active">{{ t('admin.loginLogs') }}</Link>
                <Link :href="endpoints.audit_logs" class="ym-log-tab">{{ t('admin.auditLogs') }}</Link>
            </div>

            <div class="ym-log-filters">
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('admin.searchIp')"
                    class="ym-log-search"
                />
                <select v-model="status" class="ym-log-filter-select">
                    <option value="">{{ t('admin.allStatus') }}</option>
                    <option value="success">{{ t('admin.success') }}</option>
                    <option value="failed">{{ t('admin.failed') }}</option>
                </select>
                <select v-model="device" class="ym-log-filter-select">
                    <option value="">{{ t('admin.allDevices') }}</option>
                    <option value="desktop">Desktop</option>
                    <option value="mobile">Mobile</option>
                </select>
                <button v-if="hasActiveFilters" @click="resetFilters" class="ym-log-clear-btn">
                    {{ t('admin.clear') }}
                </button>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ t('admin.status') }}</th>
                            <th class="ym-th">{{ t('admin.user') }}</th>
                            <th class="ym-th">{{ t('admin.identifier') }}</th>
                            <th class="ym-th">{{ t('admin.ipAddress') }}</th>
                            <th class="ym-th">{{ t('admin.device') }}</th>
                            <th class="ym-th ym-th--sortable" @click="toggleSort">
                                {{ t('admin.time') }}
                                <span class="ym-sort-icon">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="ym-td ym-td--empty">{{ t('admin.noLogs') }}</td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="ym-tr">
                            <td class="ym-td">
                                <span :class="['ym-action-badge', statusBadgeClass(log.status)]">
                                    {{ statusLabel(log) }}
                                </span>
                            </td>
                            <td class="ym-td font-medium">{{ log.user?.name ?? '—' }}</td>
                            <td class="ym-td text-neutral-500">{{ log.attempted_identifier ? `@${log.attempted_identifier}` : '—' }}</td>
                            <td class="ym-td font-mono text-sm">{{ log.ip_address }}</td>
                            <td class="ym-td">
                                <span :class="['ym-device-badge', log.device_type === 'mobile' ? 'ym-device-mobile' : 'ym-device-desktop']">
                                    {{ log.device_type }}
                                </span>
                            </td>
                            <td class="ym-td text-neutral-500">{{ formatDate(log.logged_in_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="logs.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in logs.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </AppLayout>
</template>
