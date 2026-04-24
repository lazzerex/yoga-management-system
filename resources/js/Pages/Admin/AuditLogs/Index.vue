<template>
    <AppLayout title="Logs">
        <section class="ym-surface ym-section">
            <div class="ym-log-page-head">
                <div>
                    <h2 class="ym-title">Audit Logs</h2>
                    <p class="ym-subtitle">User management actions performed by admins.</p>
                </div>
                <a href="/cms/admin/audit-logs/export" class="ym-btn-outline">Export CSV</a>
            </div>

            <div class="ym-log-tabs">
                <Link href="/cms/admin/login-logs" class="ym-log-tab">Login Logs</Link>
                <Link href="/cms/admin/audit-logs" class="ym-log-tab ym-log-tab--active">Audit Logs</Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">Performed By</th>
                            <th class="ym-th">Action</th>
                            <th class="ym-th">Target User</th>
                            <th class="ym-th">Details</th>
                            <th class="ym-th">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id" class="ym-tr">
                            <td class="ym-td">{{ log.causer?.name ?? 'System' }}</td>
                            <td class="ym-td">
                                <span :class="['ym-action-badge', actionBadgeClass(log.action)]">
                                    {{ actionLabel(log.action) }}
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
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    logs: Object,
});

const ACTION_LABELS = {
    create_user: 'Create User',
    update_user_info: 'Update Info',
    change_password: 'Change Password',
    assign_role: 'Assign Role',
    remove_role: 'Remove Role',
};

const ACTION_BADGE_CLASSES = {
    create_user: 'ym-action-badge--create',
    update_user_info: 'ym-action-badge--update',
    change_password: 'ym-action-badge--password',
    assign_role: 'ym-action-badge--role-assign',
    remove_role: 'ym-action-badge--role-remove',
};

const actionLabel = (action) => ACTION_LABELS[action] ?? action;

const actionBadgeClass = (action) => ACTION_BADGE_CLASSES[action] ?? '';

const formatMeta = (action, meta) => {
    if (!meta) return '—';

    switch (action) {
        case 'create_user':
            return `@${meta.username} · ${meta.role}`;
        case 'update_user_info': {
            const parts = [];
            if (meta.from?.name !== meta.to?.name) {
                parts.push(`name: ${meta.from.name} → ${meta.to.name}`);
            }
            if (meta.from?.email !== meta.to?.email) {
                parts.push(`email: ${meta.from.email} → ${meta.to.email}`);
            }
            return parts.join(', ') || '—';
        }
        case 'change_password':
            return 'Password changed';
        case 'assign_role':
            return `${meta.from} → ${meta.to}`;
        case 'remove_role':
            return `Was ${meta.role}`;
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
