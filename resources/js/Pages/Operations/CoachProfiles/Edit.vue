<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import MultiSelect from '@/Components/Form/MultiSelect.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';
import FileInput from '@/Components/Form/FileInput.vue';

const props = defineProps({
    coachProfile: Object,
    classTypes: Array,
    endpoints: Object,
});

const classTypeOptions = computed(() => props.classTypes.map((classType) => ({ value: classType.id, label: classType.name })));

const form = useForm({
    bio: props.coachProfile.bio ?? '',
    years_experience: props.coachProfile.years_experience !== null ? String(props.coachProfile.years_experience) : '',
    certifications: props.coachProfile.certifications ?? '',
    class_type_ids: [...props.coachProfile.class_type_ids],
    is_active: props.coachProfile.is_active,
    avatar: null,
    remove_avatar: false,
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editCoach') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editCoach') }}</h2>
        <p class="ym-subtitle">{{ coachProfile.user_name }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.yearsExperience')" :error="form.errors.years_experience">
                <TextInput v-model="form.years_experience" type="number" />
            </Field>
            <Field :label="$t('operations.specializations')" :error="form.errors.class_type_ids">
                <MultiSelect v-model="form.class_type_ids" :options="classTypeOptions" />
            </Field>
            <Field :label="$t('operations.bio')" :error="form.errors.bio">
                <Textarea v-model="form.bio" />
            </Field>
            <Field :label="$t('operations.certifications')" :error="form.errors.certifications">
                <Textarea v-model="form.certifications" />
            </Field>
            <Field :label="$t('operations.avatar')" :error="form.errors.avatar" :hint="$t('operations.avatarHint')">
                <div class="ym-avatar-field">
                    <img v-if="coachProfile.avatar_url && !form.remove_avatar" :src="coachProfile.avatar_url" class="ym-avatar-thumb" alt="" />
                    <FileInput v-model="form.avatar" accept="image/jpeg,image/png,image/webp" />
                </div>
            </Field>
            <Checkbox v-if="coachProfile.avatar_url" v-model="form.remove_avatar" :label="$t('operations.removeAvatar')" />
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
