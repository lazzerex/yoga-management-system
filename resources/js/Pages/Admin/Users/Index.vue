<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/UI/Modal.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    users: Object,
    endpoints: Object,
    auth: Object,
});

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

    <Modal
        :show="!!pendingDeleteUser"
        :title="$t('admin.deleteUserTitle')"
        @close="cancelDelete"
    >
        <p class="ym-card-note">{{ $t('admin.confirmDelete', { name: pendingDeleteUser?.name }) }}</p>

        <Checkbox v-model="deleteAck" :label="$t('admin.confirmDeleteAck')" class="mt-3" />

        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="cancelDelete">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" :disabled="!deleteAck" @click="confirmDelete">{{ $t('admin.delete') }}</button>
        </div>
    </Modal>
</template>
