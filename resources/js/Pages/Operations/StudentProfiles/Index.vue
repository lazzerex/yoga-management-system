<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/UI/Modal.vue';

defineProps({
    profiles: Object,
    stats: Object,
    endpoints: Object,
    canManage: Boolean,
});

const pendingDelete = ref(null);

const confirmDelete = () => {
    router.delete(route('operations.students.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>

<template>
    <AppLayout :title="$t('operations.studentProfiles')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.totalStudents') }}</p>
                <p class="ym-stat-value">{{ stats.total }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.activeStudentProfiles') }}</p>
                <p class="ym-stat-value">{{ stats.active }}</p>
            </div>
        </div>

        <section class="ym-surface ym-section">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="ym-title">
                        {{ $t('operations.studentProfiles') }}
                        <span class="ym-count-badge">{{ profiles.total }}</span>
                    </h2>
                    <p class="ym-subtitle">{{ $t('operations.manageStudents') }}</p>
                </div>
                <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn-sm">
                    {{ $t('operations.createStudent') }}
                </Link>
            </div>

            <div class="ym-table-wrap">
                <table class="ym-table">
                    <thead>
                        <tr>
                            <th class="ym-th">{{ $t('operations.studentUser') }}</th>
                            <th class="ym-th">{{ $t('operations.goals') }}</th>
                            <th class="ym-th">{{ $t('operations.status') }}</th>
                            <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="profile in profiles.data" :key="profile.id" class="ym-tr">
                            <td class="ym-td font-medium">{{ profile.user_name }}</td>
                            <td class="ym-td text-neutral-500">{{ profile.goals ?? '-' }}</td>
                            <td class="ym-td">
                                <span :class="['ym-role-badge', profile.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                    {{ profile.is_active ? $t('operations.active') : $t('operations.inactive') }}
                                </span>
                            </td>
                            <td v-if="canManage" class="ym-td">
                                <div class="ym-inline-actions">
                                    <Link class="ym-btn-outline" :href="route('operations.students.edit', profile.id)">
                                        {{ $t('operations.edit') }}
                                    </Link>
                                    <button type="button" class="ym-btn-danger" @click="pendingDelete = profile">
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!profiles.data.length">
                            <td class="ym-td text-neutral-500" :colspan="canManage ? 4 : 3">{{ $t('operations.noStudents') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="profiles.links.length > 3" class="ym-pagination">
                <Link
                    v-for="link in profiles.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                    preserve-scroll
                />
            </div>
        </section>

        <Modal :show="!!pendingDelete" :title="$t('operations.deleteStudentTitle')" @close="pendingDelete = null">
            <p class="ym-card-note">{{ $t('operations.confirmDeleteStudent', { name: pendingDelete?.user_name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn-outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn-danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </AppLayout>
</template>
