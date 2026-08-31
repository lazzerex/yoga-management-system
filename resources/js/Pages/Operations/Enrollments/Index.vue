<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    enrollments: Object,
    filters: Object,
});

const status = ref(props.filters.status ?? '');

watch(status, (value) => {
    router.get(route('operations.enrollments.index'), value ? { status: value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const locale = () => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US');

const statusLabel = (value) => t(`operations.enrollmentStatus${value.charAt(0).toUpperCase()}${value.slice(1)}`);

const statusClass = (value) => ({
    booked: 'ym-status-pill--booked',
    waitlisted: 'ym-status-pill--waitlist',
    cancelled: '',
}[value] ?? '');

const formatDateTime = (value) =>
    value ? new Intl.DateTimeFormat(locale(), { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';

const pendingCancel = ref(null);

const confirmCancel = () => {
    router.delete(pendingCancel.value.cancelUrl, {
        preserveScroll: true,
        onSuccess: () => (pendingCancel.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.enrollmentsTitle') }, () => page),
};
</script>

<template>
    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.enrollmentsTitle') }}
                    <span class="ym-count-badge">{{ enrollments.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.manageEnrollments') }}</p>
            </div>
            <select v-model="status" class="ym-log-filter-select">
                <option value="">{{ $t('operations.allStatuses') }}</option>
                <option value="booked">{{ $t('operations.enrollmentStatusBooked') }}</option>
                <option value="waitlisted">{{ $t('operations.enrollmentStatusWaitlisted') }}</option>
                <option value="cancelled">{{ $t('operations.enrollmentStatusCancelled') }}</option>
            </select>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.student') }}</th>
                        <th class="ym-th">{{ $t('operations.class') }}</th>
                        <th class="ym-th">{{ $t('operations.coach') }}</th>
                        <th class="ym-th">{{ $t('operations.date') }}</th>
                        <th class="ym-th">{{ $t('operations.status') }}</th>
                        <th class="ym-th">{{ $t('operations.enrolledAt') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in enrollments.data" :key="row.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ row.student_name }}</td>
                        <td class="ym-td">{{ row.class_type_name }}</td>
                        <td class="ym-td text-neutral-500">{{ row.coach_name }}</td>
                        <td class="ym-td">{{ row.session_date }} {{ row.start_time }}</td>
                        <td class="ym-td">
                            <span class="ym-status-pill" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
                        </td>
                        <td class="ym-td text-neutral-500">{{ formatDateTime(row.enrolled_at) }}</td>
                        <td class="ym-td">
                            <button
                                v-if="row.cancelUrl"
                                type="button"
                                class="ym-btn-danger"
                                @click="pendingCancel = row"
                            >
                                {{ $t('operations.cancelEnrollment') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!enrollments.data.length">
                        <td class="ym-td text-neutral-500" colspan="7">{{ $t('operations.noEnrollments') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="enrollments.links.length > 3" class="ym-pagination">
            <Link
                v-for="link in enrollments.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                preserve-scroll
            />
        </div>
    </section>

    <Modal :show="!!pendingCancel" :title="$t('operations.cancelEnrollmentTitle')" @close="pendingCancel = null">
        <p class="ym-card-note">
            {{ $t('operations.confirmCancelEnrollment', { name: pendingCancel?.student_name, class: pendingCancel?.class_type_name }) }}
        </p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingCancel = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmCancel">{{ $t('operations.cancelEnrollment') }}</button>
        </div>
    </Modal>
</template>
