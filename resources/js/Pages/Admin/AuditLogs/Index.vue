<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    logs: Object,
    filters: Object,
    endpoints: Object,
});

const search = ref(props.filters?.search ?? '');
const action = ref(props.filters?.action ?? '');
const sortDir = ref(props.filters?.sort_dir ?? 'desc');

const hasActiveFilters = computed(() => search.value || action.value);

let searchTimeout = null;

function applyFilters() {
    router.get(props.endpoints.self, {
        search: search.value || undefined,
        action: action.value || undefined,
        sort_dir: sortDir.value === 'desc' ? undefined : sortDir.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

watch([action, sortDir], applyFilters);

function toggleSort() {
    sortDir.value = sortDir.value === 'desc' ? 'asc' : 'desc';
}

function resetFilters() {
    router.get(props.endpoints.self, {}, { preserveState: false, replace: true });
}

const ACTION_BADGE_CLASSES = {
    create_user: 'ym-action-badge--create',
    update_user_info: 'ym-action-badge--update',
    change_password: 'ym-action-badge--password',
    assign_role: 'ym-action-badge--role-assign',
    remove_role: 'ym-action-badge--role-remove',
    delete_user: 'ym-action-badge--delete',
    view_student_medical_notes: 'ym-action-badge--update',
    cancel_enrollment: 'ym-action-badge--delete',
};

const actionBadgeClass = (action) => ACTION_BADGE_CLASSES[action] ?? '';

const formatMeta = (action, meta) => {
    if (!meta) return '—';

    switch (action) {
        case 'create_user':
            return `@${meta.username} · ${t(`admin.roles.${meta.role}`)}`;
        case 'update_user_info': {
            const parts = [];
            if (meta.from?.name !== meta.to?.name) {
                parts.push(`${t('admin.metaName')}: ${meta.from.name} → ${meta.to.name}`);
            }
            if (meta.from?.email !== meta.to?.email) {
                parts.push(`${t('admin.metaEmail')}: ${meta.from.email} → ${meta.to.email}`);
            }
            if (meta.from?.username !== meta.to?.username) {
                parts.push(`${t('admin.metaUsername')}: ${meta.from.username} → ${meta.to.username}`);
            }
            return parts.join(', ') || '—';
        }
        case 'change_password':
            return t('admin.passwordChanged');
        case 'assign_role':
            return `${t(`admin.roles.${meta.from}`)} → ${t(`admin.roles.${meta.to}`)}`;
        case 'remove_role':
        case 'delete_user':
            return t('admin.wasRole', { role: t(`admin.roles.${meta.role}`) });
        case 'view_student_medical_notes':
            return '—';
        case 'cancel_enrollment':
            return `${meta.class} · ${meta.session_date}`;
        default:
            return '—';
    }
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
    layout: (h, page) => h(AppLayout, { title: t('admin.auditTitle') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <div class="ym-log-page-head">
            <div>
                <h2 class="ym-title">{{ $t('admin.auditTitle') }}</h2>
                <p class="ym-subtitle">{{ $t('admin.auditSubtitle') }}</p>
            </div>
            <a :href="endpoints.export" class="ym-btn-outline">{{ $t('admin.exportCsv') }}</a>
        </div>

        <div class="ym-log-tabs">
            <Link :href="endpoints.login_logs" class="ym-log-tab">{{ $t('admin.loginLogs') }}</Link>
            <Link :href="endpoints.self" class="ym-log-tab ym-log-tab--active">{{ $t('admin.auditLogs') }}</Link>
        </div>

        <div class="ym-log-filters">
            <input
                v-model="search"
                type="search"
                :placeholder="$t('admin.searchPerformer')"
                class="ym-log-search"
            />
            <select v-model="action" class="ym-log-filter-select">
                <option value="">{{ $t('admin.allActions') }}</option>
                <option value="create_user">{{ $t('admin.auditActions.create_user') }}</option>
                <option value="update_user_info">{{ $t('admin.auditActions.update_user_info') }}</option>
                <option value="change_password">{{ $t('admin.auditActions.change_password') }}</option>
                <option value="assign_role">{{ $t('admin.auditActions.assign_role') }}</option>
                <option value="remove_role">{{ $t('admin.auditActions.remove_role') }}</option>
                <option value="delete_user">{{ $t('admin.auditActions.delete_user') }}</option>
                <option value="view_student_medical_notes">{{ $t('admin.auditActions.view_student_medical_notes') }}</option>
                <option value="cancel_enrollment">{{ $t('admin.auditActions.cancel_enrollment') }}</option>
            </select>
            <button v-if="hasActiveFilters" @click="resetFilters" class="ym-log-clear-btn">
                {{ $t('admin.clear') }}
            </button>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('admin.performedBy') }}</th>
                        <th class="ym-th">{{ $t('admin.action') }}</th>
                        <th class="ym-th">{{ $t('admin.targetUser') }}</th>
                        <th class="ym-th">{{ $t('admin.details') }}</th>
                        <th class="ym-th ym-th--sortable" @click="toggleSort">
                            {{ $t('admin.time') }}
                            <span class="ym-sort-icon">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="5" class="ym-td ym-td--empty">{{ $t('admin.noAuditLogs') }}</td>
                    </tr>
                    <tr v-for="log in logs.data" :key="log.id" class="ym-tr">
                        <td class="ym-td">{{ log.causer?.name ?? $t('admin.system') }}</td>
                        <td class="ym-td">
                            <span :class="['ym-action-badge', actionBadgeClass(log.action)]">
                                {{ $t(`admin.auditActions.${log.action}`) }}
                            </span>
                        </td>
                        <td class="ym-td">{{ log.subject_name ?? '—' }}</td>
                        <td class="ym-td ym-td--meta">{{ formatMeta(log.action, log.meta) }}</td>
                        <td class="ym-td">{{ formatDate(log.created_at) }}</td>
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
</template>
