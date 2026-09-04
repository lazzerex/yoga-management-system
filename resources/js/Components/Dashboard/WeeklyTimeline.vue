<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({ data: { type: Array, required: true } });

const dayShortKeys = [
    'dashboard.sundayShort',
    'dashboard.mondayShort',
    'dashboard.tuesdayShort',
    'dashboard.wednesdayShort',
    'dashboard.thursdayShort',
    'dashboard.fridayShort',
    'dashboard.saturdayShort',
];

const days = computed(() => Array.from({ length: 7 }, (_, offset) => {
    const date = new Date();
    date.setDate(date.getDate() + offset);
    const iso = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    return {
        key: iso,
        dayShort: t(dayShortKeys[date.getDay()]),
        date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        entries: props.data.filter((session) => session.session_date === iso),
    };
}));
</script>

<template>
    <div class="ym-timetable-scroll">
        <div class="ym-timetable">
            <div v-for="day in days" :key="day.key" class="ym-timetable-col">
                <div class="ym-timetable-head">
                    <p class="ym-timetable-day">{{ day.dayShort }}</p>
                    <p class="ym-timetable-date">{{ day.date }}</p>
                </div>
                <div class="ym-timetable-body">
                    <div v-for="entry in day.entries" :key="entry.id" class="ym-timetable-slot ym-timetable-slot--coach">
                        <p class="ym-timetable-time">{{ entry.start_time }}</p>
                        <p class="ym-timetable-name">{{ entry.class_type_name }}</p>
                        <p class="ym-timetable-sub">{{ entry.room_name }} · {{ $t('dashboard.studentsBooked', { count: entry.students }) }}</p>
                    </div>
                    <div v-if="!day.entries.length" class="ym-timetable-empty">
                        {{ $t('dashboard.noClassesAssigned') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
