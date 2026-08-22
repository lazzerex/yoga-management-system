<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    classSchedule: Object,
    branches: Array,
    rooms: Array,
    classTypes: Array,
    coachProfiles: Array,
    endpoints: Object,
});

const branchOptions = computed(() => props.branches.map((b) => ({ value: String(b.id), label: b.name })));
const classTypeOptions = computed(() => props.classTypes.map((c) => ({ value: String(c.id), label: c.name })));
const coachOptions = computed(() => props.coachProfiles.map((c) => ({ value: String(c.id), label: c.name })));
const dayOptions = [
    { value: '1', label: 'operations.dayMonday' },
    { value: '2', label: 'operations.dayTuesday' },
    { value: '3', label: 'operations.dayWednesday' },
    { value: '4', label: 'operations.dayThursday' },
    { value: '5', label: 'operations.dayFriday' },
    { value: '6', label: 'operations.daySaturday' },
    { value: '0', label: 'operations.daySunday' },
];

const form = useForm({
    branch_id: String(props.classSchedule.branch_id),
    room_id: String(props.classSchedule.room_id),
    class_type_id: String(props.classSchedule.class_type_id),
    coach_profile_id: String(props.classSchedule.coach_profile_id),
    day_of_week: String(props.classSchedule.day_of_week),
    start_time: props.classSchedule.start_time,
    duration_minutes: String(props.classSchedule.duration_minutes),
    capacity: String(props.classSchedule.capacity),
    is_active: props.classSchedule.is_active,
});

const roomOptions = computed(() =>
    props.rooms
        .filter((room) => String(room.branch_id) === form.branch_id)
        .map((room) => ({ value: String(room.id), label: room.name }))
);

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>

<template>
    <AppLayout :title="$t('operations.editSchedule')">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">{{ $t('operations.editSchedule') }}</h2>

            <form class="ym-form-grid" @submit.prevent="submit">
                <Field :label="$t('operations.branch')" :error="form.errors.branch_id">
                    <Select v-model="form.branch_id" :options="branchOptions" />
                </Field>
                <Field :label="$t('operations.room')" :error="form.errors.room_id">
                    <Select v-model="form.room_id" :options="roomOptions" />
                </Field>
                <Field :label="$t('operations.class')" :error="form.errors.class_type_id">
                    <Select v-model="form.class_type_id" :options="classTypeOptions" />
                </Field>
                <Field :label="$t('operations.coach')" :error="form.errors.coach_profile_id">
                    <Select v-model="form.coach_profile_id" :options="coachOptions" />
                </Field>
                <Field :label="$t('operations.dayOfWeek')" :error="form.errors.day_of_week">
                    <Select v-model="form.day_of_week" :options="dayOptions.map((d) => ({ value: d.value, label: $t(d.label) }))" />
                </Field>
                <Field :label="$t('operations.startTime')" :error="form.errors.start_time">
                    <TextInput v-model="form.start_time" type="time" />
                </Field>
                <Field :label="$t('operations.durationMinutes')" :error="form.errors.duration_minutes">
                    <TextInput v-model="form.duration_minutes" type="number" />
                </Field>
                <Field :label="$t('operations.capacity')" :error="form.errors.capacity">
                    <TextInput v-model="form.capacity" type="number" />
                </Field>
                <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ $t('operations.saveSchedule') }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
