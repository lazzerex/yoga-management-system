<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    myEnrollments: Array,
    openSessionsCount: Number,
    cancelCutoffHours: Number,
});

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US'));

const typePalette = ['#5f9e7a', '#5b8c9e', '#b0894f', '#8f7fb0', '#5c9c8a'];
const typeColor = (id) => typePalette[id % typePalette.length];

const ymd = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const todayStr = ymd(new Date());

const diffDays = (iso) =>
    Math.round((new Date(`${iso}T00:00:00`) - new Date(`${todayStr}T00:00:00`)) / 86400000);

const relLabel = (iso) => {
    const diff = diffDays(iso);
    if (diff <= 0) return t('member.today');
    if (diff === 1) return t('member.tomorrow');
    return t('member.inDays', { count: diff });
};

const formatDate = (iso) =>
    new Intl.DateTimeFormat(locale.value, { weekday: 'short', month: 'short', day: 'numeric' }).format(new Date(`${iso}T00:00:00`));

const formatLongDate = (iso) =>
    new Intl.DateTimeFormat(locale.value, { weekday: 'long', month: 'long', day: 'numeric' }).format(new Date(`${iso}T00:00:00`));

const formatDateTime = (iso) =>
    iso ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '—';

const durationMin = (item) => {
    const [sh, sm] = item.start_time.split(':').map(Number);
    const [eh, em] = item.end_time.split(':').map(Number);
    return eh * 60 + em - (sh * 60 + sm);
};

const sortedEnrollments = computed(() =>
    [...props.myEnrollments].sort((a, b) => (a.session_date + a.start_time).localeCompare(b.session_date + b.start_time)),
);
const nextBooked = computed(() => sortedEnrollments.value.find((e) => e.status === 'booked') ?? null);
const bookedCount = computed(() => props.myEnrollments.filter((e) => e.status === 'booked').length);
const waitCount = computed(() => props.myEnrollments.filter((e) => e.status === 'waitlisted').length);

const PAGE_SIZE = 8;
const currentPage = ref(1);
const totalPages = computed(() => Math.max(1, Math.ceil(sortedEnrollments.value.length / PAGE_SIZE)));
const pagedEnrollments = computed(() =>
    sortedEnrollments.value.slice((currentPage.value - 1) * PAGE_SIZE, currentPage.value * PAGE_SIZE),
);
watch(totalPages, (n) => {
    if (currentPage.value > n) currentPage.value = n;
});

const detail = ref(null);
const confirmingCancel = ref(false);
const cancelling = ref(false);

const openDetail = (item) => {
    detail.value = item;
    confirmingCancel.value = false;
};

const closeDetail = () => {
    detail.value = null;
    confirmingCancel.value = false;
};

const confirmCancel = () => {
    cancelling.value = true;
    router.delete(detail.value.cancelUrl, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => closeDetail(),
        onFinish: () => (cancelling.value = false),
    });
};

const mapsUrl = (address) => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;

const downloadIcs = (item) => {
    const stamp = (d, tm) => `${d.replace(/-/g, '')}T${tm.replace(':', '')}00`;
    const now = new Date().toISOString().replace(/[-:]|\.\d{3}/g, '');
    const lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Serenity Yoga//Booking//EN',
        'BEGIN:VEVENT',
        `UID:enrollment-${item.id}@serenity-yoga`,
        `DTSTAMP:${now}`,
        `DTSTART:${stamp(item.session_date, item.start_time)}`,
        `DTEND:${stamp(item.session_date, item.end_time)}`,
        `SUMMARY:${item.class_type_name}`,
        `LOCATION:${item.branch_name}\\, ${item.room_name}\\, ${item.branch_address}`,
        `DESCRIPTION:${item.coach_name}`,
        'END:VEVENT',
        'END:VCALENDAR',
    ];
    const blob = new Blob([lines.join('\r\n')], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${item.class_type_name.replace(/\s+/g, '-').toLowerCase()}-${item.session_date}.ics`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.myClassesMember') }, () => page),
};
</script>

<template>
    <div class="ym-book">
        <div class="ym-book-layout ym-book-layout--aside">
            <main class="ym-book-main-col">
                <section class="ym-book-card">
                    <div class="ym-book-card-head">
                        <h2 class="ym-book-card-title">
                            {{ $t('member.myBookings') }}
                            <span v-if="myEnrollments.length" class="ym-book-chip">{{ myEnrollments.length }}</span>
                        </h2>
                        <Link :href="route('member.classes.book')" class="ym-book-today">{{ $t('member.tabBook') }}</Link>
                    </div>
                    <TransitionGroup tag="div" name="ym-list" class="ym-bk-list">
                        <div
                            v-for="item in pagedEnrollments"
                            :key="item.id"
                            class="ym-bk-row ym-bk-row--btn"
                            role="button"
                            tabindex="0"
                            :style="{ '--accent': typeColor(item.class_type_id ?? 0) }"
                            @click="openDetail(item)"
                            @keydown.enter="openDetail(item)"
                            @keydown.space.prevent="openDetail(item)"
                        >
                            <div class="ym-bk-when">
                                <span class="ym-bk-rel">{{ relLabel(item.session_date) }}</span>
                                <span class="ym-bk-date">{{ formatDate(item.session_date) }}</span>
                                <span class="ym-bk-time">{{ item.start_time }}–{{ item.end_time }}</span>
                            </div>
                            <div class="ym-bk-info">
                                <p class="ym-bk-name"><span class="ym-bk-swatch" /> {{ item.class_type_name }}</p>
                                <p class="ym-bk-sub">{{ item.coach_name }} · {{ item.branch_name }} · {{ item.room_name }}</p>
                            </div>
                            <div class="ym-bk-act">
                                <span class="ym-bk-status" :class="item.status === 'booked' ? 'is-booked' : 'is-wait'">
                                    {{ item.status === 'booked'
                                        ? $t('member.statusBooked')
                                        : $t('member.waitlistPosition', { position: item.waitlist_position }) }}
                                </span>
                                <i class="bi bi-chevron-right ym-bk-chevron" />
                            </div>
                        </div>
                        <div v-if="!myEnrollments.length" key="empty" class="ym-book-empty">
                            <i class="bi bi-journal-bookmark" />
                            <p>{{ $t('member.noClasses') }}</p>
                            <Link :href="route('member.classes.book')" class="ym-book-next-cta">
                                {{ $t('member.browseSessions') }} <i class="bi bi-arrow-right" />
                            </Link>
                        </div>
                    </TransitionGroup>

                    <div v-if="totalPages > 1" class="ym-bk-pager">
                        <button type="button" :disabled="currentPage === 1" @click="currentPage--">
                            <i class="bi bi-chevron-left" />
                        </button>
                        <span>{{ currentPage }} / {{ totalPages }}</span>
                        <button type="button" :disabled="currentPage === totalPages" @click="currentPage++">
                            <i class="bi bi-chevron-right" />
                        </button>
                    </div>

                    <p v-if="myEnrollments.length" class="ym-book-hint ym-book-hint--inset">
                        <i class="bi bi-info-circle" /> {{ $t('member.cancelWindowNote', { hours: cancelCutoffHours }) }}
                    </p>
                </section>
            </main>

            <aside class="ym-book-aside">
                <section class="ym-book-card">
                    <div class="ym-book-card-head">
                        <h2 class="ym-book-card-title">{{ $t('member.nextSession') }}</h2>
                    </div>
                    <div class="ym-book-card-body">
                        <template v-if="nextBooked">
                            <span class="ym-book-sum-rel">{{ relLabel(nextBooked.session_date) }}</span>
                            <p class="ym-book-next-name" :style="{ '--accent': typeColor(nextBooked.class_type_id ?? 0) }">
                                <span class="ym-book-sum-swatch" /> {{ nextBooked.class_type_name }}
                            </p>
                            <ul class="ym-book-next-facts">
                                <li><i class="bi bi-calendar3" /> {{ formatDate(nextBooked.session_date) }}</li>
                                <li><i class="bi bi-clock" /> {{ nextBooked.start_time }}–{{ nextBooked.end_time }}</li>
                                <li><i class="bi bi-person" /> {{ nextBooked.coach_name }}</li>
                                <li><i class="bi bi-geo-alt" /> {{ nextBooked.branch_name }} · {{ nextBooked.room_name }}</li>
                            </ul>
                            <button type="button" class="ym-book-sum-more" @click="openDetail(nextBooked)">
                                {{ $t('member.bookingDetails') }} <i class="bi bi-arrow-right" />
                            </button>
                        </template>
                        <template v-else>
                            <p class="ym-book-next-empty">{{ $t('member.noUpcomingBooked') }}</p>
                            <Link :href="route('member.classes.book')" class="ym-book-next-cta">
                                {{ $t('member.browseSessions') }} <i class="bi bi-arrow-right" />
                            </Link>
                        </template>
                    </div>
                </section>

                <div class="ym-book-stats">
                    <div class="ym-book-stat">
                        <span class="ym-book-stat-num">{{ bookedCount }}</span>
                        <span class="ym-book-stat-label">{{ $t('member.statusBooked') }}</span>
                    </div>
                    <div class="ym-book-stat">
                        <span class="ym-book-stat-num">{{ waitCount }}</span>
                        <span class="ym-book-stat-label">{{ $t('member.waitlisted') }}</span>
                    </div>
                    <div class="ym-book-stat">
                        <span class="ym-book-stat-num">{{ openSessionsCount }}</span>
                        <span class="ym-book-stat-label">{{ $t('member.openSessions') }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <Modal :show="!!detail" :title="$t('member.bookingDetails')" @close="closeDetail">
        <div v-if="detail" class="ym-detail">
            <div class="ym-detail-hero" :style="{ '--accent': typeColor(detail.class_type_id ?? 0) }">
                <span class="ym-detail-tag">{{ detail.class_type_name }}</span>
                <span
                    class="ym-bk-status"
                    :class="detail.status === 'booked' ? 'is-booked' : 'is-wait'"
                >
                    {{ detail.status === 'booked'
                        ? $t('member.statusBooked')
                        : $t('member.waitlistPosition', { position: detail.waitlist_position }) }}
                </span>
            </div>

            <div v-if="detail.status === 'waitlisted'" class="ym-detail-note">
                <i class="bi bi-hourglass-split" />
                <span>{{ $t('member.waitlistExplain', { position: detail.waitlist_position }) }}</span>
            </div>

            <div v-if="detail.class_type_description" class="ym-detail-block">
                <p class="ym-detail-label">{{ $t('member.classSection') }}</p>
                <p class="ym-detail-text">{{ detail.class_type_description }}</p>
            </div>

            <div class="ym-detail-grid">
                <div class="ym-detail-block">
                    <p class="ym-detail-label">{{ $t('member.scheduleSection') }}</p>
                    <p class="ym-detail-strong">{{ formatLongDate(detail.session_date) }}</p>
                    <p class="ym-detail-text">
                        {{ detail.start_time }}–{{ detail.end_time }} · {{ $t('member.durationValue', { count: durationMin(detail) }) }}
                    </p>
                </div>
                <div class="ym-detail-block">
                    <p class="ym-detail-label">{{ $t('operations.coach') }}</p>
                    <p class="ym-detail-strong">{{ detail.coach_name }}</p>
                    <p v-if="detail.coach_years_experience" class="ym-detail-text">
                        {{ $t('member.yearsExp', { count: detail.coach_years_experience }) }}
                    </p>
                    <p v-if="detail.coach_bio" class="ym-detail-text">{{ detail.coach_bio }}</p>
                </div>
                <div class="ym-detail-block">
                    <p class="ym-detail-label">{{ $t('member.locationSection') }}</p>
                    <p class="ym-detail-strong">{{ detail.branch_name }} · {{ detail.room_name }}</p>
                    <p class="ym-detail-text">{{ detail.branch_address }}</p>
                    <a class="ym-detail-link" :href="mapsUrl(detail.branch_address)" target="_blank" rel="noopener">
                        <i class="bi bi-geo-alt" /> {{ $t('member.directions') }}
                    </a>
                </div>
                <div class="ym-detail-block">
                    <p class="ym-detail-label">{{ $t('member.capacitySection') }}</p>
                    <div class="ym-sc-cap">
                        <span class="ym-sc-cap-bar">
                            <span
                                class="ym-sc-cap-fill"
                                :class="detail.spots_left === 0 ? 'is-full' : detail.spots_left <= 3 ? 'is-limited' : 'is-open'"
                                :style="{ width: `${Math.min(100, (detail.booked_count / detail.capacity) * 100)}%` }"
                            />
                        </span>
                    </div>
                    <p class="ym-detail-text">
                        {{ $t('member.spotsFilled', { booked: detail.booked_count, capacity: detail.capacity }) }}
                        <template v-if="detail.waitlist_count">· {{ $t('member.onWaitlist', { count: detail.waitlist_count }) }}</template>
                    </p>
                </div>
            </div>

            <div class="ym-detail-block">
                <p class="ym-detail-label">{{ $t('member.yourBookingSection') }}</p>
                <p class="ym-detail-text">{{ $t('member.enrolledOn', { date: formatDateTime(detail.enrolled_at) }) }}</p>
                <p class="ym-detail-text" :class="{ 'ym-detail-warn': !detail.can_cancel }">
                    <i class="bi" :class="detail.can_cancel ? 'bi-unlock' : 'bi-lock'" />
                    {{ detail.can_cancel
                        ? $t('member.cancelBy', { date: formatDateTime(detail.cancel_deadline) })
                        : $t('member.cancelPassedNote') }}
                </p>
            </div>

            <div class="ym-detail-actions">
                <button type="button" class="ym-btn-outline" @click="downloadIcs(detail)">
                    <i class="bi bi-calendar-plus" /> {{ $t('member.addToCalendar') }}
                </button>

                <template v-if="detail.can_cancel && !confirmingCancel">
                    <button type="button" class="ym-btn-danger" @click="confirmingCancel = true">
                        {{ $t('member.cancelBooking') }}
                    </button>
                </template>
                <template v-else-if="confirmingCancel">
                    <span class="ym-detail-confirm-q">{{ $t('member.confirmCancelBooking') }}</span>
                    <button type="button" class="ym-btn-outline" @click="confirmingCancel = false">
                        {{ $t('member.keepBooking') }}
                    </button>
                    <button type="button" class="ym-btn-danger" :disabled="cancelling" @click="confirmCancel">
                        <i v-if="cancelling" class="bi bi-arrow-repeat ym-spin" />
                        <template v-else>{{ $t('member.cancelBooking') }}</template>
                    </button>
                </template>
            </div>
        </div>
    </Modal>
</template>
