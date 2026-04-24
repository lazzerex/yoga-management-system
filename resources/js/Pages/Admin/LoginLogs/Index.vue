<template>
    <AppLayout title="Logs">
        <section class="ym-surface ym-section">
            <div class="ym-log-page-head">
                <div>
                    <h2 class="ym-title">Login Activity</h2>
                    <p class="ym-subtitle">Recent sign-in events across all accounts.</p>
                </div>
                <div class="ym-log-head-actions">
                    <a href="/cms/admin/login-logs/export" class="ym-btn-outline">Export CSV</a>
                    <Link href="/cms/admin/users" class="ym-btn-ghost">Users</Link>
                </div>
            </div>

            <div class="ym-log-tabs">
                <Link href="/cms/admin/login-logs" class="ym-log-tab ym-log-tab--active">Login Logs</Link>
                <Link href="/cms/admin/audit-logs" class="ym-log-tab">Audit Logs</Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">User</th>
                            <th class="ym-th">Username</th>
                            <th class="ym-th">IP Address</th>
                            <th class="ym-th">Device</th>
                            <th class="ym-th">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ log.user?.name ?? 'Deleted user' }}</td>
                            <td class="ym-td text-neutral-500">{{ log.user ? `@${log.user.username}` : '—' }}</td>
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

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    logs: Object,
});

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
