<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { currentLocale, trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import TabBar from '@/Components/UI/TabBar.vue';

defineProps({
    branches: Object,
    branchStats: Object,
    rooms: Object,
    roomStats: Object,
    classTypes: Object,
    classTypeStats: Object,
    endpoints: Object,
    canManage: Boolean,
});

const tabs = computed(() => {
    currentLocale.value;
    return [
        { key: 'branches', label: t('operations.branches') },
        { key: 'rooms', label: t('operations.rooms') },
        { key: 'classTypes', label: t('operations.classTypes') },
    ];
});
const activeTab = ref('branches');

const pendingDeleteBranch = ref(null);
const pendingDeleteRoom = ref(null);
const pendingDeleteClassType = ref(null);

const confirmDeleteBranch = () => {
    router.delete(route('operations.branches.destroy', pendingDeleteBranch.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDeleteBranch.value = null),
    });
};

const confirmDeleteRoom = () => {
    router.delete(route('operations.rooms.destroy', pendingDeleteRoom.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDeleteRoom.value = null),
    });
};

const confirmDeleteClassType = () => {
    router.delete(route('operations.class-types.destroy', pendingDeleteClassType.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDeleteClassType.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.yogaCenter') }, () => page),
};
</script>


<template>
    <TabBar v-model="activeTab" :tabs="tabs" />

    <template v-if="activeTab === 'branches'">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.totalBranches') }}</p>
                <p class="ym-stat-value">{{ branchStats.total }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.activeBranches') }}</p>
                <p class="ym-stat-value">{{ branchStats.active }}</p>
            </div>
        </div>

        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('operations.branches') }}
                        <span class="ym-count-badge">{{ branches.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('operations.manageBranches') }}</p>
                </div>
                <Link v-if="endpoints.createBranch" :href="endpoints.createBranch" class="ym-btn-sm">
                    {{ $t('operations.createBranch') }}
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.branchName') }}</th>
                            <th class="ym-th">{{ $t('operations.address') }}</th>
                            <th class="ym-th">{{ $t('operations.phone') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="branch in branches.data" :key="branch.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ branch.name }}</td>
                            <td class="ym-td text-neutral-500">{{ branch.address }}</td>
                            <td class="ym-td text-neutral-500">{{ branch.phone ?? '-' }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', branch.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                    {{ branch.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link class="ym-btn-outline" :href="route('operations.branches.edit', branch.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button type="button" class="ym-btn-danger" @click="pendingDeleteBranch = branch">
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!branches.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 5 : 4">{{ $t('operations.noBranches') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="branches.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in branches.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </template>

    <template v-else-if="activeTab === 'rooms'">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.totalRooms') }}</p>
                <p class="ym-stat-value">{{ roomStats.total }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.activeRooms') }}</p>
                <p class="ym-stat-value">{{ roomStats.active }}</p>
            </div>
        </div>

        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('operations.rooms') }}
                        <span class="ym-count-badge">{{ rooms.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('operations.manageRooms') }}</p>
                </div>
                <Link v-if="endpoints.createRoom" :href="endpoints.createRoom" class="ym-btn-sm">
                    {{ $t('operations.createRoom') }}
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.roomName') }}</th>
                            <th class="ym-th">{{ $t('operations.branch') }}</th>
                            <th class="ym-th">{{ $t('operations.capacity') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="room in rooms.data" :key="room.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ room.name }}</td>
                            <td class="ym-td text-neutral-500">{{ room.branch_name }}</td>
                            <td class="ym-td text-neutral-500">{{ room.capacity }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', room.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                    {{ room.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link class="ym-btn-outline" :href="route('operations.rooms.edit', room.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button type="button" class="ym-btn-danger" @click="pendingDeleteRoom = room">
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!rooms.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 5 : 4">{{ $t('operations.noRooms') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="rooms.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in rooms.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </template>

    <template v-else>
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.totalClassTypes') }}</p>
                <p class="ym-stat-value">{{ classTypeStats.total }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.activeClassTypes') }}</p>
                <p class="ym-stat-value">{{ classTypeStats.active }}</p>
            </div>
        </div>

        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('operations.classTypes') }}
                        <span class="ym-count-badge">{{ classTypes.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('operations.manageClassTypes') }}</p>
                </div>
                <Link v-if="endpoints.createClassType" :href="endpoints.createClassType" class="ym-btn-sm">
                    {{ $t('operations.createClassType') }}
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.classTypeName') }}</th>
                            <th class="ym-th">{{ $t('operations.description') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="classType in classTypes.data" :key="classType.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ classType.name }}</td>
                            <td class="ym-td text-neutral-500">{{ classType.description ?? '-' }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', classType.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                    {{ classType.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link class="ym-btn-outline" :href="route('operations.class-types.edit', classType.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button type="button" class="ym-btn-danger" @click="pendingDeleteClassType = classType">
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!classTypes.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 4 : 3">{{ $t('operations.noClassTypes') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="classTypes.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in classTypes.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>
    </template>

    <Modal :show="!!pendingDeleteBranch" :title="$t('operations.deleteBranchTitle')" @close="pendingDeleteBranch = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteBranch', { name: pendingDeleteBranch?.name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDeleteBranch = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDeleteBranch">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>

    <Modal :show="!!pendingDeleteRoom" :title="$t('operations.deleteRoomTitle')" @close="pendingDeleteRoom = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteRoom', { name: pendingDeleteRoom?.name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDeleteRoom = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDeleteRoom">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>

    <Modal :show="!!pendingDeleteClassType" :title="$t('operations.deleteClassTypeTitle')" @close="pendingDeleteClassType = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteClassType', { name: pendingDeleteClassType?.name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDeleteClassType = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDeleteClassType">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
