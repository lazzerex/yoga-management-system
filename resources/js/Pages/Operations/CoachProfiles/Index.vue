<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Modal from '@/Components/UI/Modal.vue';

defineProps({
    profiles: Object,
    stats: Object,
    endpoints: Object,
    canManage: Boolean,
});

const pendingDelete = ref(null);

const confirmDelete = () => {
    router.delete(route('operations.coaches.destroy', pendingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.coaches') }, () => page),
};
</script>


<template>
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.totalCoaches') }}</p>
            <p class="ym-stat-value">{{ stats.total }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.activeCoaches') }}</p>
            <p class="ym-stat-value">{{ stats.active }}</p>
        </div>
    </div>

    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.coaches') }}
                    <span class="ym-count-badge">{{ profiles.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.manageCoaches') }}</p>
            </div>
            <Link v-if="endpoints.create" :href="endpoints.create" class="ym-btn-sm">
                {{ $t('operations.createCoach') }}
            </Link>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.avatar') }}</th>
                        <th class="ym-th">{{ $t('operations.coachUser') }}</th>
                        <th class="ym-th">{{ $t('operations.yearsExperience') }}</th>
                        <th class="ym-th">{{ $t('operations.specializations') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th v-if="canManage" class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="profile in profiles.data" :key="profile.id" class="ym-tr">
                        <td class="ym-td">
                            <img v-if="profile.avatar_url" :src="profile.avatar_url" class="ym-avatar-thumb" alt="" />
                            <span v-else class="ym-avatar-thumb ym-avatar-thumb--empty">{{ profile.user_name.charAt(0) }}</span>
                        </td>
                        <td class="ym-td font-medium">{{ profile.user_name }}</td>
                        <td class="ym-td text-neutral-500">{{ profile.years_experience ?? '-' }}</td>
                        <td class="ym-td text-neutral-500">{{ profile.class_types.join(', ') || '-' }}</td>
                        <td class="ym-td">
                            <span :class="['ym-role-badge', profile.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                {{ profile.is_active ? $t('operations.active') : $t('operations.inactive') }}
                            </span>
                        </td>
                        <td v-if="canManage" class="ym-td">
                            <div class="ym-inline-actions">
                                <Link class="ym-btn-outline" :href="route('operations.coaches.edit', profile.id)">
                                    {{ $t('operations.edit') }}
                                </Link>
                                <button type="button" class="ym-btn-danger" @click="pendingDelete = profile">
                                    {{ $t('operations.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!profiles.data.length">
                        <td class="ym-td text-neutral-500" :colspan="canManage ? 6 : 5">{{ $t('operations.noCoaches') }}</td>
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

    <Modal :show="!!pendingDelete" :title="$t('operations.deleteCoachTitle')" @close="pendingDelete = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteCoach', { name: pendingDelete?.user_name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
