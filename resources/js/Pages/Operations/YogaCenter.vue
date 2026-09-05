<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { currentLocale, trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import TabBar from '@/Components/UI/TabBar.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    branches: Object,
    branchStats: Object,
    rooms: Object,
    roomStats: Object,
    classTypes: Object,
    classTypeStats: Object,
    filters: Object,
    endpoints: Object,
    canManage: Boolean,
});

// One box narrows all three tabs; they are three views of the same centre.
const { filters, active, filterCount, reset } = useFilters(props.endpoints.index, props.filters);

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
    <div class="ym-ui">
        <TabBar v-model="activeTab" :tabs="tabs" />

        <template v-if="activeTab === 'branches'">
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">
                        {{ $t('operations.branches') }}
                        <span class="ym-count">{{ branches.total }}</span>
                    </h1>
                    <p class="ym-page-sub">{{ $t('operations.manageBranches') }}</p>
                </div>
                <div class="ym-page-actions">
                    <Link v-if="endpoints.createBranch" :href="endpoints.createBranch" class="ym-btn ym-btn--primary">
                        <i class="bi bi-plus-lg" /> {{ $t('operations.createBranch') }}
                    </Link>
                </div>
            </header>

            <div class="ym-stats">
                <div class="ym-stat-card">
                    <p class="ym-stat-card-label">{{ $t('operations.totalBranches') }}</p>
                    <p class="ym-stat-card-value">{{ branchStats.total }}</p>
                </div>
                <div class="ym-stat-card ym-stat-card--info">
                    <p class="ym-stat-card-label">{{ $t('operations.activeBranches') }}</p>
                    <p class="ym-stat-card-value">{{ branchStats.active }}</p>
                </div>
            </div>

            <section class="ym-card">
                <div class="ym-filter-band">
                    <FilterBar
                        v-model:search="filters.search"
                        :search-placeholder="$t('operations.searchByName')"
                        :count="filterCount"
                        :active="active"
                        @reset="reset"
                    >
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.status') }}</span>
                            <select v-model="filters.status" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allStatuses') }}</option>
                                <option value="active">{{ $t('operations.statusActive') }}</option>
                                <option value="inactive">{{ $t('operations.statusInactive') }}</option>
                            </select>
                        </label>
                    </FilterBar>
                </div>

                <div class="ym-table-scroll">
                    <table class="ym-grid-table">
                        <thead>
                            <tr>
                                <th>{{ $t('operations.branchName') }}</th>
                                <th>{{ $t('operations.address') }}</th>
                                <th>{{ $t('operations.phone') }}</th>
                                <th>{{ $t('operations.status') }}</th>
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="branch in branches.data" :key="branch.id">
                                <td class="is-strong">{{ branch.name }}</td>
                                <td class="is-muted">{{ branch.address }}</td>
                                <td class="is-muted ym-num">{{ branch.phone ?? '—' }}</td>
                                <td>
                                    <span class="ym-tag" :class="branch.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                        {{ branch.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                    </span>
                                </td>
                                <td v-if="canManage" class="is-actions">
                                    <div class="ym-row-actions">
                                        <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="route('operations.branches.edit', branch.id)">
                                            {{ $t('operations.edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                            @click="pendingDeleteBranch = branch"
                                        >
                                            {{ $t('operations.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!branches.data.length" class="ym-empty">
                    <i class="bi bi-building" />
                    <p>{{ $t('operations.noBranches') }}</p>
                </div>

                <div v-if="branches.links.length > 3" class="ym-card-foot">
                    <div class="ym-pagination">
                        <Link
                            v-for="link in branches.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </section>
        </template>

        <template v-else-if="activeTab === 'rooms'">
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">
                        {{ $t('operations.rooms') }}
                        <span class="ym-count">{{ rooms.total }}</span>
                    </h1>
                    <p class="ym-page-sub">{{ $t('operations.manageRooms') }}</p>
                </div>
                <div class="ym-page-actions">
                    <Link v-if="endpoints.createRoom" :href="endpoints.createRoom" class="ym-btn ym-btn--primary">
                        <i class="bi bi-plus-lg" /> {{ $t('operations.createRoom') }}
                    </Link>
                </div>
            </header>

            <div class="ym-stats">
                <div class="ym-stat-card">
                    <p class="ym-stat-card-label">{{ $t('operations.totalRooms') }}</p>
                    <p class="ym-stat-card-value">{{ roomStats.total }}</p>
                </div>
                <div class="ym-stat-card ym-stat-card--info">
                    <p class="ym-stat-card-label">{{ $t('operations.activeRooms') }}</p>
                    <p class="ym-stat-card-value">{{ roomStats.active }}</p>
                </div>
            </div>

            <section class="ym-card">
                <div class="ym-filter-band">
                    <FilterBar
                        v-model:search="filters.search"
                        :search-placeholder="$t('operations.searchByName')"
                        :count="filterCount"
                        :active="active"
                        @reset="reset"
                    >
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.status') }}</span>
                            <select v-model="filters.status" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allStatuses') }}</option>
                                <option value="active">{{ $t('operations.statusActive') }}</option>
                                <option value="inactive">{{ $t('operations.statusInactive') }}</option>
                            </select>
                        </label>
                    </FilterBar>
                </div>

                <div class="ym-table-scroll">
                    <table class="ym-grid-table">
                        <thead>
                            <tr>
                                <th>{{ $t('operations.roomName') }}</th>
                                <th>{{ $t('operations.branch') }}</th>
                                <th class="is-num">{{ $t('operations.capacity') }}</th>
                                <th>{{ $t('operations.status') }}</th>
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="room in rooms.data" :key="room.id">
                                <td class="is-strong">{{ room.name }}</td>
                                <td class="is-muted">{{ room.branch_name }}</td>
                                <td class="is-num is-muted">{{ room.capacity }}</td>
                                <td>
                                    <span class="ym-tag" :class="room.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                        {{ room.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                    </span>
                                </td>
                                <td v-if="canManage" class="is-actions">
                                    <div class="ym-row-actions">
                                        <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="route('operations.rooms.edit', room.id)">
                                            {{ $t('operations.edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                            @click="pendingDeleteRoom = room"
                                        >
                                            {{ $t('operations.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!rooms.data.length" class="ym-empty">
                    <i class="bi bi-door-open" />
                    <p>{{ $t('operations.noRooms') }}</p>
                </div>

                <div v-if="rooms.links.length > 3" class="ym-card-foot">
                    <div class="ym-pagination">
                        <Link
                            v-for="link in rooms.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </section>
        </template>

        <template v-else>
            <header class="ym-page-head">
                <div>
                    <h1 class="ym-page-title">
                        {{ $t('operations.classTypes') }}
                        <span class="ym-count">{{ classTypes.total }}</span>
                    </h1>
                    <p class="ym-page-sub">{{ $t('operations.manageClassTypes') }}</p>
                </div>
                <div class="ym-page-actions">
                    <Link v-if="endpoints.createClassType" :href="endpoints.createClassType" class="ym-btn ym-btn--primary">
                        <i class="bi bi-plus-lg" /> {{ $t('operations.createClassType') }}
                    </Link>
                </div>
            </header>

            <div class="ym-stats">
                <div class="ym-stat-card">
                    <p class="ym-stat-card-label">{{ $t('operations.totalClassTypes') }}</p>
                    <p class="ym-stat-card-value">{{ classTypeStats.total }}</p>
                </div>
                <div class="ym-stat-card ym-stat-card--info">
                    <p class="ym-stat-card-label">{{ $t('operations.activeClassTypes') }}</p>
                    <p class="ym-stat-card-value">{{ classTypeStats.active }}</p>
                </div>
            </div>

            <section class="ym-card">
                <div class="ym-filter-band">
                    <FilterBar
                        v-model:search="filters.search"
                        :search-placeholder="$t('operations.searchByName')"
                        :count="filterCount"
                        :active="active"
                        @reset="reset"
                    >
                        <label class="ym-filter-field">
                            <span>{{ $t('operations.status') }}</span>
                            <select v-model="filters.status" class="ym-log-filter-select">
                                <option value="">{{ $t('operations.allStatuses') }}</option>
                                <option value="active">{{ $t('operations.statusActive') }}</option>
                                <option value="inactive">{{ $t('operations.statusInactive') }}</option>
                            </select>
                        </label>
                    </FilterBar>
                </div>

                <div class="ym-table-scroll">
                    <table class="ym-grid-table">
                        <thead>
                            <tr>
                                <th>{{ $t('operations.classTypeName') }}</th>
                                <th>{{ $t('operations.description') }}</th>
                                <th>{{ $t('operations.status') }}</th>
                                <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="classType in classTypes.data" :key="classType.id">
                                <td class="is-strong">{{ classType.name }}</td>
                                <td class="is-muted">{{ classType.description ?? '—' }}</td>
                                <td>
                                    <span class="ym-tag" :class="classType.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                        {{ classType.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                    </span>
                                </td>
                                <td v-if="canManage" class="is-actions">
                                    <div class="ym-row-actions">
                                        <Link
                                            class="ym-btn ym-btn--outline ym-btn--sm"
                                            :href="route('operations.class-types.edit', classType.id)"
                                        >
                                            {{ $t('operations.edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                            @click="pendingDeleteClassType = classType"
                                        >
                                            {{ $t('operations.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!classTypes.data.length" class="ym-empty">
                    <i class="bi bi-collection" />
                    <p>{{ $t('operations.noClassTypes') }}</p>
                </div>

                <div v-if="classTypes.links.length > 3" class="ym-card-foot">
                    <div class="ym-pagination">
                        <Link
                            v-for="link in classTypes.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </section>
        </template>

        <Modal :show="!!pendingDeleteBranch" :title="$t('operations.deleteBranchTitle')" @close="pendingDeleteBranch = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteBranch', { name: pendingDeleteBranch?.name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDeleteBranch = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDeleteBranch">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>

        <Modal :show="!!pendingDeleteRoom" :title="$t('operations.deleteRoomTitle')" @close="pendingDeleteRoom = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteRoom', { name: pendingDeleteRoom?.name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDeleteRoom = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDeleteRoom">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>

        <Modal :show="!!pendingDeleteClassType" :title="$t('operations.deleteClassTypeTitle')" @close="pendingDeleteClassType = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteClassType', { name: pendingDeleteClassType?.name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDeleteClassType = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDeleteClassType">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
