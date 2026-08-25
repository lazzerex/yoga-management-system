<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    myEnrollments: Array,
    availableSessions: Object,
});

const statusLabel = (status) => (status === 'booked' ? t('member.statusBooked') : t('member.statusWaitlisted'));

const nextSession = computed(() => props.myEnrollments.find((e) => e.status === 'booked') ?? null);

const bookingSessionId = ref(null);

const book = (session) => {
    bookingSessionId.value = session.id;
    router.post(session.bookUrl, {}, {
        preserveScroll: true,
        onFinish: () => (bookingSessionId.value = null),
    });
};

const pendingCancel = ref(null);

const confirmCancel = () => {
    router.delete(pendingCancel.value.cancelUrl, {
        preserveScroll: true,
        onSuccess: () => (pendingCancel.value = null),
    });
};
</script>

<template>
    <AppLayout :title="$t('member.myClassesMember')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('member.enrolled') }}</p>
                <p class="ym-stat-value">{{ myEnrollments.length }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('member.nextSession') }}</p>
                <p class="ym-stat-value">{{ nextSession ? `${nextSession.session_date} ${nextSession.start_time}` : '—' }}</p>
                <p v-if="nextSession" class="ym-stat-note">{{ nextSession.class_type_name }}</p>
            </div>
        </div>

        <div class="ym-page-cols ym-page-cols--6040">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-table ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.enrolled') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-table-wrap">
                        <table class="ym-table">
                            <thead>
                                <tr>
                                    <th class="ym-th">{{ $t('operations.class') }}</th>
                                    <th class="ym-th">{{ $t('operations.date') }}</th>
                                    <th class="ym-th">{{ $t('operations.teacher') }}</th>
                                    <th class="ym-th">{{ $t('operations.status') }}</th>
                                    <th class="ym-th">{{ $t('operations.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in myEnrollments" :key="item.id" class="ym-tr">
                                    <td class="ym-td font-medium">{{ item.class_type_name }}</td>
                                    <td class="ym-td">{{ item.session_date }} {{ item.start_time }}</td>
                                    <td class="ym-td">{{ item.coach_name }}</td>
                                    <td class="ym-td">
                                        <span class="ym-status-pill">{{ statusLabel(item.status) }}</span>
                                    </td>
                                    <td class="ym-td">
                                        <button type="button" class="ym-btn-danger" @click="pendingCancel = item">
                                            {{ $t('member.cancelBooking') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!myEnrollments.length">
                                    <td class="ym-td text-neutral-500" colspan="5">{{ $t('member.noClasses') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-calendar-plus ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('member.availableSessions') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="session in availableSessions.data" :key="session.id" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ session.class_type_name }}</p>
                                <p class="ym-row-meta">{{ session.session_date }} {{ session.start_time }} · {{ session.coach_name }} · {{ session.branch_name }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span v-if="session.isFull" class="ym-tag">{{ $t('member.full') }}</span>
                                <button
                                    type="button"
                                    class="ym-btn-sm"
                                    :disabled="bookingSessionId === session.id"
                                    @click="book(session)"
                                >
                                    {{ $t('member.book') }}
                                </button>
                            </div>
                        </div>
                        <div v-if="!availableSessions.data.length" class="ym-info-row">
                            <i class="bi bi-info-circle ym-info-icon" />
                            <span>{{ $t('member.noAvailableSessions') }}</span>
                        </div>
                    </div>

                    <div v-if="availableSessions.links.length > 3" class="ym-pagination">
                        <Link
                            v-for="link in availableSessions.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="!!pendingCancel" :title="$t('member.cancelBookingTitle')" @close="pendingCancel = null">
            <p class="ym-card-note">{{ $t('member.confirmCancelBooking') }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn-outline" @click="pendingCancel = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn-danger" @click="confirmCancel">{{ $t('member.cancelBooking') }}</button>
            </div>
        </Modal>
    </AppLayout>
</template>
