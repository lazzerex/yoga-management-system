<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as t, getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    sessions: { type: Array, required: true },
    week: { type: String, required: true },
    reloadOnly: { type: Array, required: true },
    emptyText: { type: String, default: '' },
});

const emit = defineEmits(['select']);

const HOUR_REM = 3.4;

const locale = computed(() => (getActiveLanguage() === 'vi' ? 'vi-VN' : 'en-GB'));

const ymd = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const parseDay = (iso) => new Date(`${iso}T00:00:00`);
const minutes = (hhmm) => Number(hhmm.slice(0, 2)) * 60 + Number(hhmm.slice(3, 5));

const now = ref(new Date());
let clock = null;
onMounted(() => (clock = setInterval(() => (now.value = new Date()), 60000)));
onBeforeUnmount(() => clearInterval(clock));

const todayIso = computed(() => ymd(now.value));

const days = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const date = parseDay(props.week);
        date.setDate(date.getDate() + i);
        const iso = ymd(date);
        return {
            iso,
            weekday: new Intl.DateTimeFormat(locale.value, { weekday: 'short' }).format(date),
            day: date.getDate(),
            isToday: iso === todayIso.value,
            sessions: props.sessions
                .filter((s) => s.session_date === iso)
                .sort((a, b) => a.start_time.localeCompare(b.start_time)),
        };
    }),
);

const rangeLabel = computed(() => {
    const start = parseDay(props.week);
    const end = parseDay(props.week);
    end.setDate(end.getDate() + 6);
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric' }).formatRange(start, end);
});

const isCurrentWeek = computed(() => days.value.some((d) => d.isToday));

const hours = computed(() => {
    if (!props.sessions.length) return { from: 7, to: 21 };
    const from = Math.min(...props.sessions.map((s) => Math.floor(minutes(s.start_time) / 60)));
    const to = Math.max(...props.sessions.map((s) => Math.ceil(minutes(s.end_time) / 60)));
    return { from: Math.max(0, Math.min(from, 7)), to: Math.min(24, Math.max(to, from + 6)) };
});

const hourMarks = computed(() => Array.from({ length: hours.value.to - hours.value.from }, (_, i) => hours.value.from + i));

// Overlapping sessions share the column side by side instead of stacking on top of each other.
const placed = (daySessions) => {
    const result = [];
    let cluster = [];
    let clusterEnd = -1;

    const flush = () => {
        const lanes = [];
        cluster.forEach((item) => {
            let lane = lanes.findIndex((end) => end <= item.start);
            if (lane === -1) lane = lanes.length;
            lanes[lane] = item.end;
            item.lane = lane;
        });
        cluster.forEach((item) => result.push({ ...item, lanes: lanes.length }));
        cluster = [];
    };

    daySessions.forEach((session) => {
        const item = { session, start: minutes(session.start_time), end: minutes(session.end_time) };
        if (item.start >= clusterEnd) flush();
        cluster.push(item);
        clusterEnd = Math.max(clusterEnd, item.end);
    });
    flush();

    return result.map((item) => ({
        session: item.session,
        style: {
            top: `${((item.start - hours.value.from * 60) / 60) * HOUR_REM}rem`,
            height: `${Math.max(((item.end - item.start) / 60) * HOUR_REM, 1.6)}rem`,
            left: `calc(${(item.lane / item.lanes) * 100}% + 2px)`,
            width: `calc(${100 / item.lanes}% - 4px)`,
        },
    }));
};

const nowTop = computed(() => {
    const mins = now.value.getHours() * 60 + now.value.getMinutes();
    if (mins < hours.value.from * 60 || mins > hours.value.to * 60) return null;
    return `${((mins - hours.value.from * 60) / 60) * HOUR_REM}rem`;
});

const loading = ref(false);

const goTo = (offsetDays) => {
    let week = ymd(new Date());
    if (offsetDays !== null) {
        const date = parseDay(props.week);
        date.setDate(date.getDate() + offsetDays);
        week = ymd(date);
    }
    loading.value = true;
    router.reload({ data: { week }, only: props.reloadOnly, onFinish: () => (loading.value = false) });
};

const accentOf = (session) => ['#5f9e7a', '#5b8c9e', '#b0894f', '#8f7fb0', '#5c9c8a', '#c07a6a'][(session.class_type_id ?? 0) % 6];
</script>

<template>
    <section class="ym-card ym-wcal" :class="{ 'is-loading': loading }">
        <div class="ym-wcal-bar">
            <div class="ym-wcal-nav">
                <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" :aria-label="$t('common.previousWeek')" @click="goTo(-7)">
                    <i class="bi bi-chevron-left" />
                </button>
                <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" :disabled="isCurrentWeek" @click="goTo(null)">
                    {{ $t('common.thisWeek') }}
                </button>
                <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" :aria-label="$t('common.nextWeek')" @click="goTo(7)">
                    <i class="bi bi-chevron-right" />
                </button>
            </div>
            <p class="ym-wcal-range">{{ rangeLabel }}</p>
            <slot name="toolbar" />
        </div>

        <div class="ym-wcal-scroll">
            <div class="ym-wcal-grid">
                <div class="ym-wcal-corner" />
                <div v-for="day in days" :key="day.iso" class="ym-wcal-head" :class="{ 'is-today': day.isToday }">
                    <span class="ym-wcal-wd">{{ day.weekday }}</span>
                    <span class="ym-wcal-dn">{{ day.day }}</span>
                </div>

                <div class="ym-wcal-hours" :style="{ height: `${hourMarks.length * HOUR_REM}rem` }">
                    <span v-for="hour in hourMarks" :key="hour" class="ym-wcal-hour">{{ String(hour).padStart(2, '0') }}:00</span>
                </div>
                <div
                    v-for="day in days"
                    :key="`col-${day.iso}`"
                    class="ym-wcal-col"
                    :class="{ 'is-today': day.isToday }"
                    :style="{ height: `${hourMarks.length * HOUR_REM}rem`, '--ym-hour': `${HOUR_REM}rem` }"
                >
                    <button
                        v-for="item in placed(day.sessions)"
                        :key="item.session.id"
                        type="button"
                        class="ym-wcal-event"
                        :class="`is-${item.session.status ?? 'scheduled'}`"
                        :style="{ ...item.style, '--accent': accentOf(item.session) }"
                        @click="emit('select', item.session)"
                    >
                        <span class="ym-wcal-time">{{ item.session.start_time }}-{{ item.session.end_time }}</span>
                        <slot name="event" :session="item.session">
                            <span class="ym-wcal-title">{{ item.session.class_type_name }}</span>
                        </slot>
                    </button>
                    <span v-if="day.isToday && nowTop" class="ym-wcal-now" :style="{ top: nowTop }" />
                </div>
            </div>
        </div>

        <div class="ym-wcal-agenda">
            <div v-for="day in days" :key="`ag-${day.iso}`" class="ym-wcal-agenda-day" :class="{ 'is-today': day.isToday }">
                <p class="ym-wcal-agenda-head">{{ day.weekday }} {{ day.day }}</p>
                <button
                    v-for="session in day.sessions"
                    :key="session.id"
                    type="button"
                    class="ym-wcal-event is-flat"
                    :class="`is-${session.status ?? 'scheduled'}`"
                    :style="{ '--accent': accentOf(session) }"
                    @click="emit('select', session)"
                >
                    <span class="ym-wcal-time">{{ session.start_time }}-{{ session.end_time }}</span>
                    <slot name="event" :session="session">
                        <span class="ym-wcal-title">{{ session.class_type_name }}</span>
                    </slot>
                </button>
                <p v-if="!day.sessions.length" class="ym-wcal-agenda-empty">-</p>
            </div>
        </div>

        <p v-if="!sessions.length && emptyText" class="ym-wcal-empty">
            <i class="bi bi-calendar-x" /> {{ emptyText }}
        </p>
    </section>
</template>
