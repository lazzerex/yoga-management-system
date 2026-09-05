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
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.editCoach') }}</h1>
                <p class="ym-page-sub">{{ coachProfile.user_name }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
                        <Field :label="$t('operations.yearsExperience')" :error="form.errors.years_experience">
                            <TextInput v-model="form.years_experience" type="number" />
                        </Field>
                        <Field :label="$t('operations.specializations')" :error="form.errors.class_type_ids">
                            <MultiSelect v-model="form.class_type_ids" :options="classTypeOptions" />
                        </Field>
                        <Field class="ym-form-span" :label="$t('operations.bio')" :error="form.errors.bio">
                            <Textarea v-model="form.bio" />
                        </Field>
                        <Field class="ym-form-span" :label="$t('operations.certifications')" :error="form.errors.certifications">
                            <Textarea v-model="form.certifications" />
                        </Field>
                        <Field
                            class="ym-form-span"
                            :label="$t('operations.avatar')"
                            :error="form.errors.avatar"
                            :hint="$t('operations.avatarHint')"
                        >
                            <div class="ym-avatar-row">
                                <img
                                    v-if="coachProfile.avatar_url && !form.remove_avatar"
                                    :src="coachProfile.avatar_url"
                                    class="ym-thumb"
                                    alt=""
                                />
                                <FileInput v-model="form.avatar" accept="image/jpeg,image/png,image/webp" />
                            </div>
                        </Field>
                    </div>

                    <div class="ym-stack mt-4">
                        <Checkbox v-if="coachProfile.avatar_url" v-model="form.remove_avatar" :label="$t('operations.removeAvatar')" />
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
