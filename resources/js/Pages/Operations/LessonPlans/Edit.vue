<script setup>
import { computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import FileInput from '@/Components/Form/FileInput.vue';

const props = defineProps({
    plan: Object,
    options: Object,
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
    branch_id: String(props.plan.branch_id),
    class_type_id: String(props.plan.class_type_id),
    class_session_id: props.plan.class_session_id ? String(props.plan.class_session_id) : '',
    title: props.plan.title,
    objective: props.plan.objective ?? '',
    asana_sequence: props.plan.asana_sequence,
    duration_minutes: String(props.plan.duration_minutes),
    level: props.plan.level,
    attachments: [],
});

const submit = () => {
    form.patch(props.endpoints.update);
};

const removeAttachment = (attachment) => {
    router.delete(attachment.deleteUrl, { preserveScroll: true });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editLessonPlan') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editLessonPlan') }}</h2>
        <p v-if="plan.status === 'rejected'" class="ym-subtitle">{{ $t('operations.planResubmitHint') }}</p>

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

            <Field :label="$t('operations.planAttachments')" :error="form.errors.attachments" :hint="$t('operations.attachmentHint')" bare>
                <ul v-if="plan.attachments.length" class="ym-file-list">
                    <li v-for="attachment in plan.attachments" :key="attachment.id" class="ym-file-row">
                        <a :href="attachment.showUrl" target="_blank" class="ym-file-name">{{ attachment.name }}</a>
                        <button v-if="attachment.deleteUrl" type="button" class="ym-btn-danger" @click="removeAttachment(attachment)">
                            {{ $t('operations.delete') }}
                        </button>
                    </li>
                </ul>
                <FileInput v-model="form.attachments" multiple accept="application/pdf,image/jpeg,image/png" />
            </Field>

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('common.save') }}
                </button>
                <Link :href="endpoints.show" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
