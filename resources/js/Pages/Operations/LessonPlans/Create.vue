<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Textarea from '@/Components/Form/Textarea.vue';

const props = defineProps({
    options: Object,
    selectedBranchId: Number,
    endpoints: Object,
});

const branchOptions = computed(() => props.options.branches.map((branch) => ({ value: String(branch.id), label: branch.name })));
const classTypeOptions = computed(() => props.options.classTypes.map((type) => ({ value: String(type.id), label: type.name })));
const levelOptions = computed(() => props.options.levels.map((level) => ({
    value: level,
    label: t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`),
})));
const sessionOptions = computed(() => [
    { value: '', label: t('operations.planSessionNone') },
    ...props.options.sessions.map((session) => ({ value: String(session.id), label: session.label })),
]);

const form = useForm({
    branch_id: String(props.selectedBranchId ?? props.options.branches[0]?.id ?? ''),
    class_type_id: String(props.options.classTypes[0]?.id ?? ''),
    class_session_id: '',
    title: '',
    objective: '',
    asana_sequence: '',
    duration_minutes: '60',
    level: props.options.levels[0] ?? '',
});

// router.reload() from the branch switcher updates props without remounting, so seed again by hand.
watch(() => props.selectedBranchId, (branchId) => {
    if (branchId) {
        form.branch_id = String(branchId);
    }
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.createLessonPlan') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.createLessonPlan') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.lessonPlansSubtitle') }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.planTitle')" :error="form.errors.title">
                <TextInput v-model="form.title" />
            </Field>
            <Field :label="$t('operations.planBranch')" :error="form.errors.branch_id">
                <Select v-model="form.branch_id" :options="branchOptions" />
            </Field>
            <Field :label="$t('operations.planClassType')" :error="form.errors.class_type_id">
                <Select v-model="form.class_type_id" :options="classTypeOptions" />
            </Field>
            <Field :label="$t('operations.planSession')" :error="form.errors.class_session_id">
                <Select v-model="form.class_session_id" :options="sessionOptions" />
            </Field>
            <Field :label="$t('operations.planLevel')" :error="form.errors.level">
                <Select v-model="form.level" :options="levelOptions" />
            </Field>
            <Field :label="$t('operations.planDuration')" :error="form.errors.duration_minutes">
                <TextInput v-model="form.duration_minutes" type="number" />
            </Field>
            <Field :label="$t('operations.planObjective')" :error="form.errors.objective">
                <Textarea v-model="form.objective" />
            </Field>
            <Field :label="$t('operations.planAsanaSequence')" :error="form.errors.asana_sequence">
                <Textarea v-model="form.asana_sequence" />
            </Field>

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('common.create') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
