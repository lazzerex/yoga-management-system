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
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.editStudent') }}</h1>
                <p class="ym-page-sub">{{ studentProfile.user_name }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
                        <Field :label="$t('operations.emergencyContactName')" :error="form.errors.emergency_contact_name">
                            <TextInput v-model="form.emergency_contact_name" />
                        </Field>
                        <Field :label="$t('operations.emergencyContactPhone')" :error="form.errors.emergency_contact_phone">
                            <TextInput v-model="form.emergency_contact_phone" />
                        </Field>
                        <Field class="ym-form-span" :label="$t('operations.goals')" :error="form.errors.goals">
                            <Textarea v-model="form.goals" />
                        </Field>
                        <Field
                            v-if="canViewMedical"
                            class="ym-form-span"
                            :label="$t('operations.medicalNotes')"
                            :error="form.errors.medical_notes"
                        >
                            <Textarea v-model="form.medical_notes" />
                        </Field>
                        <p v-else class="ym-callout ym-form-span">
                            <i class="bi bi-shield-lock" />
                            <span>{{ $t('operations.medicalNotesRestricted') }}</span>
                        </p>
                        <Field
                            class="ym-form-span"
                            :label="$t('operations.avatar')"
                            :error="form.errors.avatar"
                            :hint="$t('operations.avatarHint')"
                        >
                            <div class="ym-avatar-row">
                                <img
                                    v-if="studentProfile.avatar_url && !form.remove_avatar"
                                    :src="studentProfile.avatar_url"
                                    class="ym-thumb"
                                    alt=""
                                />
                                <FileInput v-model="form.avatar" accept="image/jpeg,image/png,image/webp" />
                            </div>
                        </Field>
                    </div>

                    <div class="ym-stack mt-4">
                        <Checkbox v-if="studentProfile.avatar_url" v-model="form.remove_avatar" :label="$t('operations.removeAvatar')" />
                        <Checkbox v-model="form.is_active" :label="$t('operations.active')" />
                    </div>
                </div>

                <div class="ym-form-foot">
                    <Link :href="endpoints.index" class="ym-btn ym-btn--quiet">{{ $t('common.cancel') }}</Link>
                    <button type="submit" class="ym-btn ym-btn--primary" :disabled="form.processing">
                        {{ $t('common.save') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</template>
