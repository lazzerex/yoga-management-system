<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';
import FileInput from '@/Components/Form/FileInput.vue';

const props = defineProps({
    studentProfile: Object,
    canViewMedical: Boolean,
    endpoints: Object,
});

const form = useForm({
    emergency_contact_name: props.studentProfile.emergency_contact_name ?? '',
    emergency_contact_phone: props.studentProfile.emergency_contact_phone ?? '',
    goals: props.studentProfile.goals ?? '',
    is_active: props.studentProfile.is_active,
    avatar: null,
    remove_avatar: false,
    ...(props.canViewMedical ? { medical_notes: props.studentProfile.medical_notes ?? '' } : {}),
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editStudent') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editStudent') }}</h2>
        <p class="ym-subtitle">{{ studentProfile.user_name }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.emergencyContactName')" :error="form.errors.emergency_contact_name">
                <TextInput v-model="form.emergency_contact_name" />
            </Field>
            <Field :label="$t('operations.emergencyContactPhone')" :error="form.errors.emergency_contact_phone">
                <TextInput v-model="form.emergency_contact_phone" />
            </Field>
            <Field :label="$t('operations.goals')" :error="form.errors.goals">
                <Textarea v-model="form.goals" />
            </Field>
            <Field v-if="canViewMedical" :label="$t('operations.medicalNotes')" :error="form.errors.medical_notes">
                <Textarea v-model="form.medical_notes" />
            </Field>
            <p v-else class="ym-card-note">{{ $t('operations.medicalNotesRestricted') }}</p>
            <Field :label="$t('operations.avatar')" :error="form.errors.avatar" :hint="$t('operations.avatarHint')">
                <div class="ym-avatar-field">
                    <img v-if="studentProfile.avatar_url && !form.remove_avatar" :src="studentProfile.avatar_url" class="ym-avatar-thumb" alt="" />
                    <FileInput v-model="form.avatar" accept="image/jpeg,image/png,image/webp" />
                </div>
            </Field>
            <Checkbox v-if="studentProfile.avatar_url" v-model="form.remove_avatar" :label="$t('operations.removeAvatar')" />
            <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('common.save') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
