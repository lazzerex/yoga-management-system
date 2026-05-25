<template>
    <AppLayout title="Logs">
        <section class="ym-surface ym-section">
            <div class="ym-log-page-head">
                <div>
                    <h2 class="ym-title">Audit Logs</h2>
                    <p class="ym-subtitle">User management actions performed by admins.</p>
                </div>
                <a :href="endpoints.export" class="ym-btn-outline">Export CSV</a>
            </div>

            <div class="ym-log-tabs">
                <Link :href="endpoints.login_logs" class="ym-log-tab">Login Logs</Link>
                <Link :href="endpoints.self" class="ym-log-tab ym-log-tab--active">Audit Logs</Link>
            </div>

            <div class="ym-log-filters">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search by performer or target..."
                    class="ym-log-search"
                />
                <select v-model="action" class="ym-log-filter-select">
                    <option value="">All Actions</option>
                    <option value="create_user">Create User</option>
                    <option value="update_user_info">Update Info</option>
                    <option value="change_password">Change Password</option>
                    <option value="assign_role">Assign Role</option>
                    <option value="remove_role">Remove Role</option>
                </select>
                <button v-if="hasActiveFilters" @click="resetFilters" class="ym-log-clear-btn">
                    Clear
                </button>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">Performed By</th>
                            <th class="ym-th">Action</th>
                            <th class="ym-th">Target User</th>
                            <th class="ym-th">Details</th>
                            <th class="ym-th ym-th--sortable" @click="toggleSort">
                                Time
                                <span class="ym-sort-icon">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="ym-td ym-td--empty">No audit logs found.</td>
                        </tr>
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
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

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
            if (meta.from?.username !== meta.to?.username) {
                parts.push(`username: ${meta.from.username} -> ${meta.to.username}`);
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
