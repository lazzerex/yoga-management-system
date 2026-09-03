<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import MoneyInput from '@/Components/Form/MoneyInput.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    plan: Object,
    options: Object,
    endpoints: Object,
});

const branchOptions = computed(() => [
    { value: '', label: t('operations.allBranches') },
    ...props.options.branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
]);
const typeOptions = computed(() => props.options.types.map((type) => ({
    value: type,
    label: t(`operations.tuitionType${type.charAt(0).toUpperCase()}${type.slice(1)}`),
})));

const form = useForm({
    branch_id: props.plan.branch_id ? String(props.plan.branch_id) : '',
    name: props.plan.name,
    type: props.plan.type,
    price_amount: String(props.plan.price_amount),
    session_count: props.plan.session_count ? String(props.plan.session_count) : '',
    duration_days: props.plan.duration_days ? String(props.plan.duration_days) : '',
    description: props.plan.description ?? '',
    is_active: props.plan.is_active,
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editTuitionPlan') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editTuitionPlan') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.tuitionPlansSubtitle') }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.tuitionPlanName')" :error="form.errors.name">
                <TextInput v-model="form.name" />
            </Field>
            <Field :label="$t('operations.tuitionPlanType')" :error="form.errors.type">
                <Select v-model="form.type" :options="typeOptions" />
            </Field>
            <Field :label="$t('operations.amountVnd')" :error="form.errors.price_amount">
                <MoneyInput v-model="form.price_amount" />
            </Field>
            <Field :label="$t('operations.planBranch')" :error="form.errors.branch_id">
                <Select v-model="form.branch_id" :options="branchOptions" />
            </Field>
            <Field
                :label="$t('operations.tuitionPlanSessions')"
                :error="form.errors.session_count"
                :hint="$t('operations.tuitionPlanSessionsHint')"
            >
                <TextInput v-model="form.session_count" type="number" />
            </Field>
            <Field
                :label="$t('operations.tuitionPlanDuration')"
                :error="form.errors.duration_days"
                :hint="$t('operations.tuitionPlanDurationHint')"
            >
                <TextInput v-model="form.duration_days" type="number" />
            </Field>
            <Field :label="$t('operations.description')" :error="form.errors.description">
                <TextInput v-model="form.description" />
            </Field>
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
