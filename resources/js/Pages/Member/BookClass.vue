<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    availableSessions: Array,
    entitlements: Array,
});

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-US'));

const ymd = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const todayStr = ymd(new Date());

const typePalette = ['#5f9e7a', '#5b8c9e', '#b0894f', '#8f7fb0', '#5c9c8a'];
const typeColor = (id) => typePalette[id % typePalette.length];

const initials = (name) =>
    name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

const timeBucket = (start) => {
    const hour = Number(start.slice(0, 2));
    if (hour < 12) return 'morning';
    if (hour < 17) return 'afternoon';
    return 'evening';
};

const filterType = ref('');
const filterCoach = ref('');
const filterTime = ref('');
const selectedDate = ref(null);

const timeSegments = [
    { value: '', key: 'member.anyTimeShort' },
    { value: 'morning', key: 'member.morning' },
    { value: 'afternoon', key: 'member.afternoon' },
    { value: 'evening', key: 'member.evening' },
];

const hasFilters = computed(() => filterType.value || filterCoach.value || filterTime.value || selectedDate.value);

const clearFilters = () => {
    filterType.value = '';
    filterCoach.value = '';
    filterTime.value = '';
    selectedDate.value = null;
};

const classTypeOptions = computed(() => {
    const map = new Map();
    props.availableSessions.forEach((s) => map.set(s.class_type_id, s.class_type_name));
    return [...map].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

const coachOptions = computed(() => {
    const map = new Map();
    props.availableSessions.forEach((s) => map.set(s.coach_profile_id, s.coach_name));
    return [...map].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
});

const sessionsByFilter = computed(() =>
    props.availableSessions.filter((s) => {
        if (filterType.value && s.class_type_id !== Number(filterType.value)) return false;
        if (filterCoach.value && s.coach_profile_id !== Number(filterCoach.value)) return false;
        if (filterTime.value && timeBucket(s.start_time) !== filterTime.value) return false;
        return true;
    }),
);

const visibleSessions = computed(() => {
    const list = selectedDate.value
        ? sessionsByFilter.value.filter((s) => s.session_date === selectedDate.value)
        : sessionsByFilter.value;
    return [...list].sort((a, b) => (a.session_date + a.start_time).localeCompare(b.session_date + b.start_time));
});

// Flat, keyed list of day headers and session cards. One TransitionGroup over
// this avoids the stranded-item glitches that nested per-day groups produced.
const listItems = computed(() => {
    const items = [];
    let lastDate = null;
    for (const session of visibleSessions.value) {
        if (session.session_date !== lastDate) {
            items.push({ type: 'header', key: `h-${session.session_date}`, date: session.session_date });
            lastDate = session.session_date;
        }
        items.push({ type: 'session', key: `s-${session.id}`, session });
    }
    return items;
});

const dayIndex = computed(() => {
    const index = {};
    sessionsByFilter.value.forEach((s) => {
        (index[s.session_date] ??= []).push(s);
    });
    return index;
});

const viewMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));

const shiftMonth = (delta) => {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + delta, 1);
};

const goToday = () => {
    viewMonth.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    selectedDate.value = null;
};

const monthLabel = computed(() =>
    new Intl.DateTimeFormat(locale.value, { month: 'long', year: 'numeric' }).format(viewMonth.value),
);

const weekdayLabels = computed(() =>
    ['dashboard.mondayShort', 'dashboard.tuesdayShort', 'dashboard.wednesdayShort', 'dashboard.thursdayShort',
        'dashboard.fridayShort', 'dashboard.saturdayShort', 'dashboard.sundayShort'].map((key) => t(key)),
);

const calendarCells = computed(() => {
    const first = new Date(viewMonth.value);
    const offset = (first.getDay() + 6) % 7;
    const start = new Date(first);
    start.setDate(first.getDate() - offset);

    return Array.from({ length: 42 }, (_, i) => {
        const date = new Date(start);
        date.setDate(start.getDate() + i);
        const key = ymd(date);
        const sessions = dayIndex.value[key] ?? [];
        const spots = sessions.reduce((sum, s) => sum + s.spots_left, 0);

        let state = 'none';
        if (sessions.length) state = spots === 0 ? 'full' : spots <= 3 ? 'limited' : 'open';

        return {
            key,
            day: date.getDate(),
            inMonth: date.getMonth() === viewMonth.value.getMonth(),
            isToday: key === todayStr,
            count: sessions.length,
            state,
        };
    });
});

const selectDay = (cell) => {
    if (!cell.count) return;
    selectedDate.value = selectedDate.value === cell.key ? null : cell.key;
};

const formatDate = (iso) =>
    new Intl.DateTimeFormat(locale.value, { weekday: 'short', month: 'short', day: 'numeric' }).format(new Date(`${iso}T00:00:00`));

const diffDays = (iso) =>
    Math.round((new Date(`${iso}T00:00:00`) - new Date(`${todayStr}T00:00:00`)) / 86400000);

const formatDayHeading = (iso) => {
    const long = new Intl.DateTimeFormat(locale.value, { weekday: 'long', month: 'long', day: 'numeric' }).format(new Date(`${iso}T00:00:00`));
    const diff = diffDays(iso);
    if (diff <= 0) return `${t('member.today')} · ${long}`;
    if (diff === 1) return `${t('member.tomorrow')} · ${long}`;
    return long;
};

const durationMin = (s) => {
    const [sh, sm] = s.start_time.split(':').map(Number);
    const [eh, em] = s.end_time.split(':').map(Number);
    return eh * 60 + em - (sh * 60 + sm);
};

const capacityState = (s) => (s.spots_left === 0 ? 'full' : s.spots_left <= 3 ? 'limited' : 'open');

const spotLabel = (s) => {
    if (s.spots_left === 0) return t('member.sessionFull');
    if (s.spots_left === 1) return t('member.oneSpotLeft');
    return t('member.spotsLeft', { count: s.spots_left });
};

const resultLabel = computed(() =>
    visibleSessions.value.length === 1 ? t('member.oneSessionFound') : t('member.sessionsFound', { count: visibleSessions.value.length }),
);

const bookingId = ref(null);

// The server decides whether a session can be booked and says why not. Nothing here
// recomputes that from the entitlements below, which are shown for information only.
const planSummary = computed(() =>
    props.entitlements
        .map((plan) =>
            plan.sessions_remaining === null
                ? plan.description
                : t('member.planWithSessionsLeft', { description: plan.description, count: plan.sessions_remaining }),
        )
        .join(' · '),
);

const firstBlockReason = computed(() => props.availableSessions.find((s) => s.block_reason)?.block_reason ?? null);

const allBlocked = computed(
    () => props.availableSessions.length > 0 && props.availableSessions.every((s) => s.block_reason),
);

const book = (session) => {
    if (session.block_reason) return;

    bookingId.value = session.id;
    router.post(session.bookUrl, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (bookingId.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('member.tabBook') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('member.tabBook') }}
                    <span class="ym-count">{{ visibleSessions.length }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('member.availableSessions') }}</p>
            </div>
        </header>

        <p v-if="allBlocked" class="ym-callout ym-callout--warn mb-3">
            <i class="bi bi-exclamation-triangle" />
            <span>{{ $t(firstBlockReason) }}</span>
        </p>
        <p v-else-if="planSummary" class="ym-callout ym-callout--ok mb-3">
            <i class="bi bi-patch-check" />
            <span>{{ $t('member.planInForce', { plans: planSummary }) }}</span>
        </p>

        <div class="ym-book">
            <div class="ym-book-layout">
            <aside class="ym-book-rail">
                <section class="ym-book-card">
                    <div class="ym-book-card-head">
                        <h2 class="ym-book-card-title">{{ $t('member.bookingCalendar') }}</h2>
                        <button type="button" class="ym-book-today" @click="goToday">{{ $t('member.goToday') }}</button>
                    </div>
                    <div class="ym-book-card-body">
                        <div class="ym-bcal-nav">
                            <button type="button" class="ym-bcal-btn" @click="shiftMonth(-1)" aria-label="previous month">
                                <i class="bi bi-chevron-left" />
                            </button>
                            <Transition name="ym-fade" mode="out-in">
                                <span :key="monthLabel" class="ym-bcal-title">{{ monthLabel }}</span>
                            </Transition>
                            <button type="button" class="ym-bcal-btn" @click="shiftMonth(1)" aria-label="next month">
                                <i class="bi bi-chevron-right" />
                            </button>
                        </div>

                        <div class="ym-bcal-grid ym-bcal-grid--head">
                            <span v-for="label in weekdayLabels" :key="label" class="ym-bcal-wd">{{ label }}</span>
                        </div>

                        <Transition name="ym-slide" mode="out-in">
                            <div :key="monthLabel" class="ym-bcal-grid">
                                <button
                                    v-for="cell in calendarCells"
                                    :key="cell.key"
                                    type="button"
                                    class="ym-bcal-cell"
                                    :class="{
                                        'is-muted': !cell.inMonth,
                                        'is-today': cell.isToday,
                                        'is-selected': selectedDate === cell.key,
                                        'is-disabled': !cell.count,
                                    }"
                                    @click="selectDay(cell)"
                                >
                                    <span class="ym-bcal-day">{{ cell.day }}</span>
                                    <span class="ym-bcal-dots">
                                        <span v-if="cell.state !== 'none'" class="ym-bcal-dot" :class="`is-${cell.state}`" />
                                    </span>
                                </button>
                            </div>
                        </Transition>

                        <div class="ym-bcal-legend">
                            <span><span class="ym-bcal-dot is-open" /> {{ $t('member.book') }}</span>
                            <span><span class="ym-bcal-dot is-limited" /> {{ $t('member.oneSpotLeft') }}</span>
                            <span><span class="ym-bcal-dot is-full" /> {{ $t('member.full') }}</span>
                        </div>
                    </div>
                </section>
            </aside>

            <main class="ym-book-main-col">
                <section class="ym-book-card">
                    <div class="ym-book-card-head">
                        <div>
                            <h2 class="ym-book-card-title">{{ $t('member.availableSessions') }}</h2>
                            <p class="ym-book-result">{{ resultLabel }}</p>
                        </div>
                        <button v-if="selectedDate" type="button" class="ym-book-daytag" @click="selectedDate = null">
                            {{ formatDate(selectedDate) }} <i class="bi bi-x-lg" />
                        </button>
                        <span v-else class="ym-book-scope">{{ $t('member.allUpcoming') }}</span>
                    </div>

                    <div class="ym-book-filters">
                        <div class="ym-seg">
                            <button
                                v-for="seg in timeSegments"
                                :key="seg.value"
                                type="button"
                                class="ym-seg-btn"
                                :class="{ 'is-active': filterTime === seg.value }"
                                @click="filterTime = seg.value"
                            >
                                {{ $t(seg.key) }}
                            </button>
                        </div>
                        <select v-model="filterType" class="ym-book-select">
                            <option value="">{{ $t('member.allTypes') }}</option>
                            <option v-for="opt in classTypeOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                        </select>
                        <select v-model="filterCoach" class="ym-book-select">
                            <option value="">{{ $t('member.allCoaches') }}</option>
                            <option v-for="opt in coachOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                        </select>
                        <button v-if="hasFilters" type="button" class="ym-book-clear" @click="clearFilters">
                            <i class="bi bi-x-lg" /> {{ $t('member.clearFilters') }}
                        </button>
                    </div>


                    <div v-if="!visibleSessions.length" class="ym-book-empty">
                        <i class="bi bi-wind" />
                        <p>{{ hasFilters ? $t('member.noSessionsForFilters') : $t('member.noAvailableSessions') }}</p>
                        <button v-if="hasFilters" type="button" class="ym-book-clear" @click="clearFilters">{{ $t('member.clearFilters') }}</button>
                    </div>

                    <TransitionGroup v-else tag="div" name="ym-list" class="ym-sc-list">
                        <div v-for="entry in listItems" :key="entry.key" class="ym-sc-item">
                            <h3 v-if="entry.type === 'header'" class="ym-book-group-head">
                                {{ formatDayHeading(entry.date) }}
                            </h3>
                            <article
                                v-else
                                class="ym-sc"
                                :style="{ '--accent': typeColor(entry.session.class_type_id) }"
                            >
                                <div class="ym-sc-time">
                                    <span class="ym-sc-time-start">{{ entry.session.start_time }}</span>
                                    <span class="ym-sc-time-end">{{ entry.session.end_time }}</span>
                                </div>
                                <div class="ym-sc-body">
                                    <div class="ym-sc-row">
                                        <p class="ym-sc-title"><span class="ym-sc-swatch" /> {{ entry.session.class_type_name }}</p>
                                        <span class="ym-sc-spots" :class="`is-${capacityState(entry.session)}`">{{ spotLabel(entry.session) }}</span>
                                    </div>
                                    <div class="ym-sc-coach">
                                        <span class="ym-sc-avatar">{{ initials(entry.session.coach_name) }}</span>
                                        <span class="ym-sc-coach-name">{{ entry.session.coach_name }}</span>
                                        <span class="ym-sc-dot-sep">·</span>
                                        <span>{{ entry.session.room_name }}</span>
                                        <span class="ym-sc-dot-sep">·</span>
                                        <span>{{ $t('member.durationMinutes', { count: durationMin(entry.session) }) }}</span>
                                    </div>
                                    <div class="ym-sc-cap">
                                        <span class="ym-sc-cap-bar">
                                            <span
                                                class="ym-sc-cap-fill"
                                                :class="`is-${capacityState(entry.session)}`"
                                                :style="{ width: `${Math.min(100, (entry.session.booked_count / entry.session.capacity) * 100)}%` }"
                                            />
                                        </span>
                                        <span class="ym-sc-cap-text">
                                            {{ entry.session.booked_count }}/{{ entry.session.capacity }}
                                            <template v-if="entry.session.waitlist_count">· {{ $t('member.onWaitlist', { count: entry.session.waitlist_count }) }}</template>
                                        </span>
                                    </div>
                                </div>
                                <div class="ym-sc-side">
                                    <button
                                        type="button"
                                        class="ym-sc-cta"
                                        :class="{ 'is-wait': entry.session.spots_left === 0 }"
                                        :disabled="bookingId === entry.session.id || !!entry.session.block_reason"
                                        :title="entry.session.block_reason ? $t(entry.session.block_reason) : null"
                                        @click="book(entry.session)"
                                    >
                                        <i v-if="bookingId === entry.session.id" class="bi bi-arrow-repeat ym-spin" />
                                        <i v-else-if="entry.session.block_reason" class="bi bi-lock" />
                                        <template v-else>{{ entry.session.spots_left === 0 ? $t('member.joinWaitlist') : $t('member.book') }}</template>
                                    </button>
                                </div>
                            </article>
                        </div>
                    </TransitionGroup>
                </section>
            </main>
            </div>
        </div>
    </div>
</template>
