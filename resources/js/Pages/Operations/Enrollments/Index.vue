<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import FilterBar from '@/Components/UI/FilterBar.vue';
import DateRange from '@/Components/UI/DateRange.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    enrollments: Object,
    filters: Object,
    endpoints: Object,
});

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const locale = () => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US');

const statusLabel = (value) => t(`operations.enrollmentStatus${value.charAt(0).toUpperCase()}${value.slice(1)}`);

const statusTone = {
    booked: 'ok',
    waitlisted: 'warn',
    cancelled: 'neutral',
};

const formatDateTime = (value) =>
    value ? new Intl.DateTimeFormat(locale(), { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

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
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.enrollmentsTitle') }}
                    <span class="ym-count">{{ enrollments.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.manageEnrollments') }}</p>
            </div>
        </header>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :search-placeholder="$t('operations.searchByNameOrReference')"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <label class="ym-filter-field">
                        <span>{{ $t('operations.status') }}</span>
                        <select v-model="filters.status" class="ym-log-filter-select">
                            <option value="">{{ $t('operations.allStatuses') }}</option>
                            <option value="booked">{{ $t('operations.enrollmentStatusBooked') }}</option>
                            <option value="waitlisted">{{ $t('operations.enrollmentStatusWaitlisted') }}</option>
                            <option value="cancelled">{{ $t('operations.enrollmentStatusCancelled') }}</option>
                        </select>
                    </label>
                    <DateRange v-model:from="filters.from" v-model:to="filters.to" :label="$t('operations.date')" />
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.bookingReference') }}</th>
                            <th>{{ $t('operations.student') }}</th>
                            <th>{{ $t('operations.class') }}</th>
                            <th>{{ $t('operations.coach') }}</th>
                            <th>{{ $t('operations.date') }}</th>
                            <SortTh field="status" :label="$t('operations.status')" :state="filters" @sort="toggleSort" />
                            <SortTh field="enrolled_at" :label="$t('operations.enrolledAt')" :state="filters" @sort="toggleSort" />
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in enrollments.data" :key="row.id">
                            <td class="ym-num is-muted">{{ row.reference }}</td>
                            <td class="is-strong">{{ row.student_name }}</td>
                            <td>{{ row.class_type_name }}</td>
                            <td class="is-muted">{{ row.coach_name }}</td>
                            <td class="ym-num">{{ row.session_date }} {{ row.start_time }}</td>
                            <td>
                                <span class="ym-tag" :class="`ym-tag--${statusTone[row.status] ?? 'neutral'}`">
                                    {{ statusLabel(row.status) }}
                                </span>
                            </td>
                            <td class="is-muted ym-num">{{ formatDateTime(row.enrolled_at) }}</td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <button
                                        v-if="row.cancelUrl"
                                        type="button"
                                        class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                        @click="pendingCancel = row"
                                    >
                                        {{ $t('operations.cancelEnrollment') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!enrollments.data.length" class="ym-empty">
                <i class="bi bi-journal-bookmark" />
                <p>{{ $t('operations.noEnrollments') }}</p>
            </div>

            <div v-if="enrollments.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in enrollments.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal :show="!!pendingCancel" :title="$t('operations.cancelEnrollmentTitle')" @close="pendingCancel = null">
            <p class="ym-note">
                {{ $t('operations.confirmCancelEnrollment', { name: pendingCancel?.student_name, class: pendingCancel?.class_type_name }) }}
            </p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingCancel = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmCancel">{{ $t('operations.cancelEnrollment') }}</button>
            </div>
        </Modal>
    </div>
</template>
