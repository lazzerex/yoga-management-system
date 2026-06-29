<script setup>
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: Object,
    endpoints: Object,
    auth: Object,
});

const isCurrentUser = (id) => props.auth?.user?.id === id;

const deleteUser = (user) => {
    if (isCurrentUser(user.id)) return;
    if (!window.confirm(t('admin.confirmDelete', { name: user.name }))) return;
    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="$t('admin.userManagement')">
        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('admin.users') }}
                        <span class="ym-count-badge">{{ users.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('admin.manageAccounts') }}</p>
                </div>
                <Link type="button" class="ym-btn-sm" :href="endpoints.create">
                    {{ $t('admin.createUser') }}
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('admin.name') }}</th>
                            <th class="ym-th">{{ $t('admin.username') }}</th>
                            <th class="ym-th">{{ $t('admin.email') }}</th>
                            <th class="ym-th">{{ $t('admin.role') }}</th>
                            <th class="ym-th">{{ $t('admin.lastLogin') }}</th>
                            <th class="ym-th">{{ $t('admin.joined') }}</th>
                            <th class="ym-th">{{ $t('admin.actions') }}</th>
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
                            <td class="ym-td text-neutral-500">{{ user.last_login ?? $t('admin.never') }}</td>
                            <td class="ym-td text-neutral-500">{{ user.created_at }}</td>
                            <td class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link type="button" class="ym-btn-outline" :href="route('admin.users.edit', user.id)">{{ $t('admin.edit') }}</Link>
                                    <button
                                        type="button"
                                        class="ym-btn-danger"
                                        :disabled="isCurrentUser(user.id)"
                                        @click="deleteUser(user)"
                                    >{{ $t('admin.delete') }}</button>
                                </div>
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
    </AppLayout>
</template>
