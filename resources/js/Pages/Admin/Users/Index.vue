<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/UI/Modal.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    users: Object,
    filters: Object,
    endpoints: Object,
    auth: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const roleTone = {
    admin: 'ym-tag--info',
    coach: 'ym-tag--ok',
    member: 'ym-tag--neutral',
};

const isCurrentUser = (id) => props.auth?.user?.id === id;

const pendingDeleteUser = ref(null);
const deleteAck = ref(false);

const deleteUser = (user) => {
    if (isCurrentUser(user.id)) return;
    pendingDeleteUser.value = user;
    deleteAck.value = false;
};

const cancelDelete = () => {
    pendingDeleteUser.value = null;
    deleteAck.value = false;
};

const confirmDelete = () => {
    router.delete(route('admin.users.destroy', pendingDeleteUser.value.id), {
        preserveScroll: true,
        onSuccess: cancelDelete,
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('admin.userManagement') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('admin.users') }}
                    <span class="ym-count">{{ users.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('admin.manageAccounts') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link class="ym-btn ym-btn--primary" :href="endpoints.create">
                    <i class="bi bi-plus-lg" /> {{ $t('admin.createUser') }}
                </Link>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('admin.searchUsers')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('admin.role') }}</span>
                        <select v-model="filters.role" class="ym-log-filter-select">
                            <option value="">{{ $t('admin.allRoles') }}</option>
                            <option value="admin">{{ $t('admin.roles.admin') }}</option>
                            <option value="coach">{{ $t('admin.roles.coach') }}</option>
                            <option value="member">{{ $t('admin.roles.member') }}</option>
                        </select>
                    </label>
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <SortTh field="name" :label="$t('admin.name')" :state="filters" @sort="toggleSort" />
                            <SortTh field="username" :label="$t('admin.username')" :state="filters" @sort="toggleSort" />
                            <th>{{ $t('admin.email') }}</th>
                            <SortTh field="role" :label="$t('admin.role')" :state="filters" @sort="toggleSort" />
                            <SortTh field="last_login" :label="$t('admin.lastLogin')" :state="filters" @sort="toggleSort" />
                            <SortTh field="created_at" :label="$t('admin.joined')" :state="filters" @sort="toggleSort" />
                            <th class="is-actions">{{ $t('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="is-strong">{{ user.name }}</td>
                            <td class="is-muted">@{{ user.username }}</td>
                            <td class="is-muted">{{ user.email }}</td>
                            <td>
                                <span class="ym-tag" :class="roleTone[user.role] ?? 'ym-tag--neutral'">
                                    {{ $t(`admin.roles.${user.role}`) }}
                                </span>
                            </td>
                            <td class="is-muted ym-num">{{ user.last_login ?? $t('admin.never') }}</td>
                            <td class="is-muted ym-num">{{ user.created_at }}</td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="route('admin.users.edit', user.id)">
                                        {{ $t('admin.edit') }}
                                    </Link>
                                    <button
                                        type="button"
                                        class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                        :disabled="isCurrentUser(user.id)"
                                        @click="deleteUser(user)"
                                    >
                                        {{ $t('admin.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!users.data.length" class="ym-empty">
                <i class="bi bi-people" />
                <p>{{ $t('admin.noUsers') }}</p>
            </div>

            <div v-if="users.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in users.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal :show="!!pendingDeleteUser" :title="$t('admin.deleteUserTitle')" @close="cancelDelete">
            <p class="ym-note">{{ $t('admin.confirmDelete', { name: pendingDeleteUser?.name }) }}</p>

            <Checkbox v-model="deleteAck" :label="$t('admin.confirmDeleteAck')" class="mt-3" />

            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="cancelDelete">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" :disabled="!deleteAck" @click="confirmDelete">
                    {{ $t('admin.delete') }}
                </button>
            </div>
        </Modal>
    </div>
</template>
