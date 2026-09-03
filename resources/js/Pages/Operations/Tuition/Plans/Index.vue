<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import { formatVnd } from '@/composables/useMoney.js';

defineProps({
    plans: Object,
    endpoints: Object,
});

const pendingDelete = ref(null);

const typeLabel = (type) => t(`operations.tuitionType${type.charAt(0).toUpperCase()}${type.slice(1)}`);

const confirmDelete = () => {
    router.delete(pendingDelete.value.destroyUrl, {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.tuitionPlans') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.tuitionPlans') }}
                    <span class="ym-count-badge">{{ plans.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.tuitionPlansSubtitle') }}</p>
            </div>
            <div class="ym-inline-actions">
                <Link :href="endpoints.invoices" class="ym-btn-outline">{{ $t('operations.backToInvoices') }}</Link>
                <Link :href="endpoints.create" class="ym-btn-sm">{{ $t('operations.createTuitionPlan') }}</Link>
            </div>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.tuitionPlanName') }}</th>
                        <th class="ym-th">{{ $t('operations.tuitionPlanType') }}</th>
                        <th class="ym-th">{{ $t('operations.amount') }}</th>
                        <th class="ym-th">{{ $t('operations.tuitionPlanSessions') }}</th>
                        <th class="ym-th">{{ $t('operations.tuitionPlanDuration') }}</th>
                        <th class="ym-th">{{ $t('operations.planBranch') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="plan in plans.data" :key="plan.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ plan.name }}</td>
                        <td class="ym-td text-neutral-500">{{ typeLabel(plan.type) }}</td>
                        <td class="ym-td">{{ formatVnd(plan.price_amount) }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.session_count ?? '-' }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.duration_days ?? '-' }}</td>
                        <td class="ym-td text-neutral-500">{{ plan.branch_name ?? $t('operations.allBranches') }}</td>
                        <td class="ym-td">
                            <span :class="['ym-role-badge', plan.is_active ? 'ym-role-coach' : 'ym-role-member']">
                                {{ plan.is_active ? $t('operations.active') : $t('operations.inactive') }}
                            </span>
                        </td>
                        <td class="ym-td">
                            <div class="ym-inline-actions">
                                <Link class="ym-btn-outline" :href="plan.editUrl">{{ $t('operations.edit') }}</Link>
                                <button type="button" class="ym-btn-danger" @click="pendingDelete = plan">
                                    {{ $t('operations.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!plans.data.length">
                        <td class="ym-td text-neutral-500" colspan="8">{{ $t('operations.noTuitionPlans') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="plans.links.length > 3" class="ym-pagination">
            <Link
                v-for="link in plans.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                preserve-scroll
            />
        </div>
    </section>

    <Modal :show="!!pendingDelete" :title="$t('operations.deleteTuitionPlanTitle')" @close="pendingDelete = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteTuitionPlan', { name: pendingDelete?.name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
