<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
    unread: {
        type: Number,
        required: true,
    },
    endpoints: {
        type: Object,
        required: true,
    },
});

const ICONS = {
    'tuition.due_soon': 'bi-cash-coin',
    'tuition.overdue': 'bi-exclamation-circle',
    'class.reminder': 'bi-calendar-event',
    'class.cancelled': 'bi-calendar-x',
    'enrollment.promoted': 'bi-person-check',
    'enrollment.cancelled_by_staff': 'bi-person-dash',
    'lesson_plan.submitted': 'bi-journal-arrow-up',
    'lesson_plan.reviewed': 'bi-journal-check',
    'member.registered': 'bi-person-plus',
};

const TONES = {
    'tuition.overdue': 'danger',
    'class.cancelled': 'danger',
    'enrollment.cancelled_by_staff': 'danger',
    'tuition.due_soon': 'warn',
    'enrollment.promoted': 'brand',
    'lesson_plan.reviewed': 'brand',
};

const confirmingClear = ref(false);

const rows = computed(() => props.notifications.data);
const hasUnread = computed(() => rows.value.some((item) => !item.read));

const icon = (event) => ICONS[event] ?? 'bi-bell';
const tone = (event) => TONES[event] ?? 'info';

const open = (item) => {
    router.post(item.readUrl, {}, {
        preserveScroll: true,
        onFinish: () => item.url && router.get(item.url),
    });
};

const markAllRead = () => router.post(props.endpoints.readAll, {}, { preserveScroll: true });

const clearAll = () => {
    confirmingClear.value = false;
    router.delete(props.endpoints.clear, { preserveScroll: true });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('common.notifications') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    <i class="bi bi-bell" />
                    {{ $t('common.notifications') }}
                </h1>
                <p class="ym-page-sub">
                    <span v-if="props.unread" class="ym-note-count">{{ $t('common.notificationsUnread', { count: props.unread }) }}</span>
                    <span v-if="props.unread"> &middot; </span>
                    <span>{{ $t('common.notificationsTotal', { count: props.notifications.total }) }}</span>
                </p>
            </div>

            <div class="ym-page-actions">
                <button
                    type="button"
                    class="ym-btn ym-btn--outline"
                    :disabled="!hasUnread"
                    @click="markAllRead"
                >
                    <i class="bi bi-check2-all" />
                    {{ $t('common.markAllRead') }}
                </button>
                <button
                    type="button"
                    class="ym-btn ym-btn--danger"
                    :disabled="!props.notifications.total"
                    @click="confirmingClear = true"
                >
                    <i class="bi bi-trash3" />
                    {{ $t('common.clearAll') }}
                </button>
            </div>
        </header>

        <section class="ym-card">
            <ul v-if="rows.length" class="ym-note-feed">
                <li
                    v-for="item in rows"
                    :key="item.id"
                    class="ym-note-row"
                    :class="{ 'is-unread': !item.read }"
                    role="button"
                    tabindex="0"
                    @click="open(item)"
                    @keydown.enter="open(item)"
                    @keydown.space.prevent="open(item)"
                >
                    <span class="ym-note-icon" :class="`is-${tone(item.event)}`">
                        <i class="bi" :class="icon(item.event)" />
                    </span>

                    <span class="ym-note-body">
                        <span class="ym-note-text">{{ $t(item.message, item.params) }}</span>
                        <span class="ym-note-time">{{ item.time }}</span>
                    </span>

                    <span v-if="!item.read" class="ym-note-dot" aria-hidden="true" />
                    <i class="bi bi-chevron-right ym-note-chevron" aria-hidden="true" />
                </li>
            </ul>

            <div v-else class="ym-empty">
                <i class="bi bi-bell-slash" />
                <p>{{ $t('common.noNotifications') }}</p>
            </div>

            <div v-if="props.notifications.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in props.notifications.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal
            :show="confirmingClear"
            :title="$t('common.clearNotificationsTitle')"
            @close="confirmingClear = false"
        >
            <p class="ym-note">{{ $t('common.clearNotificationsBody') }}</p>

            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="confirmingClear = false">
                    {{ $t('common.cancel') }}
                </button>
                <button type="button" class="ym-btn ym-btn--danger" @click="clearAll">
                    {{ $t('common.clearAll') }}
                </button>
            </div>
        </Modal>
    </div>
</template>
