<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/UI/Modal.vue';

defineProps({
    branches: Object,
    stats: Object,
    endpoints: Object,
    canManage: Boolean,
});

const pendingDelete = ref(null);

const confirmDelete = () => {
    router.delete(route('operations.branches.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>

<template>
    <AppLayout :title="$t('operations.yogaCenter')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.totalBranches') }}</p>
                <p class="ym-stat-value">{{ stats.total }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.activeBranches') }}</p>
                <p class="ym-stat-value">{{ stats.active }}</p>
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
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn-sm">
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
                                    <button type="button" class="ym-btn-danger" @click="pendingDelete = branch">
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

        <Modal :show="!!pendingDelete" :title="$t('operations.deleteBranchTitle')" @close="pendingDelete = null">
            <p class="ym-card-note">{{ $t('operations.confirmDeleteBranch', { name: pendingDelete?.name }) }}</p>

            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn-outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn-danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </AppLayout>
</template>
