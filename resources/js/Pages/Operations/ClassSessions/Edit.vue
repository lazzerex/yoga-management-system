<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';

const props = defineProps({
    classSession: Object,
    rooms: Array,
    coachProfiles: Array,
    endpoints: Object,
});

const roomOptions = computed(() => props.rooms.map((r) => ({ value: String(r.id), label: `${r.name} (${r.capacity})` })));
const coachOptions = computed(() => props.coachProfiles.map((c) => ({ value: String(c.id), label: c.name })));
const statusOptions = [
    { value: 'scheduled', label: 'operations.statusScheduled' },
    { value: 'cancelled', label: 'operations.statusCancelled' },
    { value: 'done', label: 'operations.statusDone' },
];

const form = useForm({
    room_id: String(props.classSession.room_id),
    coach_profile_id: String(props.classSession.coach_profile_id),
    capacity: String(props.classSession.capacity),
    status: props.classSession.status,
});

const selectedRoom = computed(() => props.rooms.find((room) => String(room.id) === form.room_id) ?? null);

const capacityOverRoom = computed(() => !!selectedRoom.value && Number(form.capacity) > selectedRoom.value.capacity);

const capacityHint = computed(() => {
    if (!selectedRoom.value) {
        return '';
    }

    return capacityOverRoom.value
        ? t('operations.capacityExceedsRoomHint', { count: selectedRoom.value.capacity })
        : t('operations.roomCapacityHint', { count: selectedRoom.value.capacity });
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editSession') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editSession') }}</h2>
        <p class="ym-subtitle">
            {{ classSession.class_type_name }} — {{ classSession.session_date }} {{ classSession.start_time }}
            ({{ classSession.branch_name }})
        </p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.room')" :error="form.errors.room_id">
                <Select v-model="form.room_id" :options="roomOptions" />
            </Field>
            <Field :label="$t('operations.coach')" :error="form.errors.coach_profile_id">
                <Select v-model="form.coach_profile_id" :options="coachOptions" />
            </Field>
            <Field :label="$t('operations.capacity')" :error="form.errors.capacity" :hint="capacityHint" :hint-warn="capacityOverRoom">
                <TextInput v-model="form.capacity" type="number" :max="selectedRoom?.capacity" />
            </Field>
            <Field :label="$t('operations.status')" :error="form.errors.status">
                <Select v-model="form.status" :options="statusOptions.map((s) => ({ value: s.value, label: $t(s.label) }))" />
            </Field>

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('operations.saveSession') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
