<template>
    <AppLayout title="User Management">
        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        Users
                        <span class="ym-count-badge">{{ users.total }}</span>
                    </h2>
                    <p class="ym-subtitle">Manage accounts and role assignments.</p>
                </div>
                <Link type="button" class="ym-btn-sm" href="/cms/admin/users/create">
                    Create User
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">Name</th>
                            <th class="ym-th">Username</th>
                            <th class="ym-th">Email</th>
                            <th class="ym-th">Role</th>
                            <th class="ym-th">Last Login</th>
                            <th class="ym-th">Joined</th>
                            <th class="ym-th">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ user.name }}</td>
                            <td class="ym-td text-neutral-500">@{{ user.username }}</td>
                            <td class="ym-td text-neutral-500">{{ user.email }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', `ym-role-${user.role}`]">{{ user.role }}</span>
                            </td>
                            <td class="ym-td text-neutral-500">{{ user.last_login ?? 'Never' }}</td>
                            <td class="ym-td text-neutral-500">{{ user.created_at }}</td>
                            <td class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link type="button" class="ym-btn-outline" :href="`/cms/admin/users/${user.id}/edit`">Edit</Link>
                                    <button
                                        type="button"
                                        class="ym-btn-danger"
                                        :disabled="isCurrentUser(user.id)"
                                        @click="deleteUser(user)"
                                    >Delete</button>
                                </div>
                                <!-- <p v-if="isCurrentUser(user.id)" class="ym-inline-hint">Current user</p> -->
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="users.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>

        <!-- Modals removed, now handled by separate pages -->
    </AppLayout>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    users: Object,
});

const page = usePage();

const isCurrentUser = (id) => page.props.auth?.user?.id === id;

const deleteUser = (user) => {
    if (isCurrentUser(user.id)) return;
    if (!window.confirm(`Delete ${user.name}? This cannot be undone.`)) return;
    router.delete(`/cms/admin/users/${user.id}`, { preserveScroll: true });
};
</script>
