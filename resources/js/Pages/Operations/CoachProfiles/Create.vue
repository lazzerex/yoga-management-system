<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import MultiSelect from '@/Components/Form/MultiSelect.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    users: Array,
    classTypes: Array,
    endpoints: Object,
});

const userOptions = computed(() => props.users.map((user) => ({ value: String(user.id), label: user.name })));
const classTypeOptions = computed(() => props.classTypes.map((classType) => ({ value: classType.id, label: classType.name })));

const form = useForm({
    user_id: props.users[0] ? String(props.users[0].id) : '',
    bio: '',
    years_experience: '',
    certifications: '',
    class_type_ids: [],
    is_active: true,
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>

<template>
    <AppLayout :title="$t('operations.createCoach')">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">{{ $t('operations.createCoach') }}</h2>
            <p class="ym-subtitle">{{ $t('operations.manageCoaches') }}</p>

            <form class="ym-form-grid" @submit.prevent="submit">
                <Field :label="$t('operations.coachUser')" :error="form.errors.user_id">
                    <Select v-model="form.user_id" :options="userOptions" />
                </Field>
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
                <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

                <div class="ym-actions">
                    <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                        {{ $t('operations.createCoach') }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
