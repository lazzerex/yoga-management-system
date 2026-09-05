<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/UI/Modal.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    profiles: Object,
    stats: Object,
    filters: Object,
    endpoints: Object,
    canManage: Boolean,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const pendingDelete = ref(null);

const confirmDelete = () => {
    router.delete(route('operations.students.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.studentProfiles') }, () => page),
};
</script>


<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.studentProfiles') }}
                    <span class="ym-count">{{ profiles.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.manageStudents') }}</p>
            </div>
            <div class="ym-page-actions">
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn ym-btn--primary">
                    <i class="bi bi-plus-lg" /> {{ $t('operations.createStudent') }}
                </Link>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.totalStudents') }}</p>
                <p class="ym-stat-card-value">{{ stats.total }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('operations.activeStudentProfiles') }}</p>
                <p class="ym-stat-card-value">{{ stats.active }}</p>
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
                            <th>{{ $t('operations.avatar') }}</th>
                            <th>{{ $t('operations.studentUser') }}</th>
                            <th>{{ $t('operations.goals') }}</th>
                            <SortTh field="is_active" :label="$t('operations.status')" :state="filters" @sort="toggleSort" />
                            <th v-if="canManage" class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="profile in profiles.data" :key="profile.id">
                            <td>
                                <img v-if="profile.avatar_url" :src="profile.avatar_url" class="ym-thumb" alt="" />
                                <span v-else class="ym-thumb ym-thumb--empty">{{ profile.user_name.charAt(0) }}</span>
                            </td>
                            <td class="is-strong">{{ profile.user_name }}</td>
                            <td class="is-muted">{{ profile.goals ?? '—' }}</td>
                            <td>
                                <span class="ym-tag" :class="profile.is_active ? 'ym-tag--ok' : 'ym-tag--neutral'">
                                    {{ profile.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="is-actions">
                                <div class="ym-row-actions">
                                    <Link class="ym-btn ym-btn--outline ym-btn--sm" :href="route('operations.students.edit', profile.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button
                                        type="button"
                                        class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                        @click="pendingDelete = profile"
                                    >
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!profiles.data.length" class="ym-empty">
                <i class="bi bi-people" />
                <p>{{ $t('operations.noStudents') }}</p>
            </div>

            <div v-if="profiles.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in profiles.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal :show="!!pendingDelete" :title="$t('operations.deleteStudentTitle')" @close="pendingDelete = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteStudent', { name: pendingDelete?.user_name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
