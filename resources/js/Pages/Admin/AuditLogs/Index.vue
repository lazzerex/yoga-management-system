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

const ACTION_BADGE_CLASSES = {
    create_user: 'ym-action-badge--create',
    update_user_info: 'ym-action-badge--update',
    change_password: 'ym-action-badge--password',
    assign_role: 'ym-action-badge--role-assign',
    remove_role: 'ym-action-badge--role-remove',
    delete_user: 'ym-action-badge--delete',
    view_student_medical_notes: 'ym-action-badge--update',
    cancel_enrollment: 'ym-action-badge--delete',
    update_setting: 'ym-action-badge--update',
    ai_attachment_sent: 'ym-action-badge--role-assign',
};

const actionBadgeClass = (action) => ACTION_BADGE_CLASSES[action] ?? '';

const formatMeta = (action, meta) => {
    if (!meta) return '-';

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
            return parts.join(', ') || '-';
        }
        case 'change_password':
            return t('admin.passwordChanged');
        case 'assign_role':
            return `${t(`admin.roles.${meta.from}`)} → ${t(`admin.roles.${meta.to}`)}`;
        case 'remove_role':
        case 'delete_user':
            return t('admin.wasRole', { role: t(`admin.roles.${meta.role}`) });
        case 'view_student_medical_notes':
            return '-';
        case 'cancel_enrollment':
            return `${meta.class} · ${meta.session_date}`;
        case 'update_setting': {
            // Rows written before batching carry one key/from/to instead of a list.
            const changes = meta.changes ?? [meta];
            return changes.map((change) => `${change.key}: ${change.from || '-'} → ${change.to}`).join(', ');
        }
        case 'ai_attachment_sent':
            return `${meta.plan_title} · ${meta.file_name}`;
        default:
            return '-';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
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
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('admin.auditTitle') }}</h1>
                <p class="ym-page-sub">{{ $t('admin.auditSubtitle') }}</p>
            </div>
            <div class="ym-page-actions">
                <a :href="endpoints.export" class="ym-btn ym-btn--export">
                    <i class="bi bi-download" /> {{ $t('admin.exportCsv') }}
                </a>
            </div>
        </header>

        <SubTabs
            group="admin-logs"
            :tabs="[
                { label: $t('admin.loginLogs'), href: endpoints.login_logs, active: false },
                { label: $t('admin.auditLogs'), href: endpoints.self, active: true },
            ]"
        />

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('admin.searchPerformer')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('admin.action') }}</span>
                        <select v-model="filters.action" class="ym-log-filter-select">
                            <option value="">{{ $t('admin.allActions') }}</option>
                            <option value="create_user">{{ $t('admin.auditActions.create_user') }}</option>
                            <option value="update_user_info">{{ $t('admin.auditActions.update_user_info') }}</option>
                            <option value="change_password">{{ $t('admin.auditActions.change_password') }}</option>
                            <option value="assign_role">{{ $t('admin.auditActions.assign_role') }}</option>
                            <option value="remove_role">{{ $t('admin.auditActions.remove_role') }}</option>
                            <option value="delete_user">{{ $t('admin.auditActions.delete_user') }}</option>
                            <option value="view_student_medical_notes">{{ $t('admin.auditActions.view_student_medical_notes') }}</option>
                            <option value="cancel_enrollment">{{ $t('admin.auditActions.cancel_enrollment') }}</option>
                            <option value="update_setting">{{ $t('admin.auditActions.update_setting') }}</option>
                            <option value="ai_attachment_sent">{{ $t('admin.auditActions.ai_attachment_sent') }}</option>
                        </select>
                    </label>
                    <DateRange v-model:from="filters.from" v-model:to="filters.to" :label="$t('admin.time')" />
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('admin.performedBy') }}</th>
                            <SortTh field="action" :label="$t('admin.action')" :state="filters" @sort="toggleSort" />
                            <th>{{ $t('admin.targetUser') }}</th>
                            <th>{{ $t('admin.details') }}</th>
                            <SortTh field="created_at" :label="$t('admin.time')" :state="filters" @sort="toggleSort" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="is-strong">{{ log.causer?.name ?? $t('admin.system') }}</td>
                            <td>
                                <span :class="['ym-action-badge', actionBadgeClass(log.action)]">
                                    {{ $t(`admin.auditActions.${log.action}`) }}
                                </span>
                            </td>
                            <td>{{ log.subject_name ?? '-' }}</td>
                            <td class="is-muted">{{ formatMeta(log.action, log.meta) }}</td>
                            <td class="is-muted ym-num">{{ formatDate(log.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!logs.data.length" class="ym-empty">
                <i class="bi bi-journal-text" />
                <p>{{ $t('admin.noAuditLogs') }}</p>
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
