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
    branch_id: '',
    name: '',
    type: props.options.types[0],
    price_amount: '0',
    session_count: '',
    duration_days: '',
    description: '',
    is_active: true,
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.createTuitionPlan') }, () => page),
};
</script>

<template>
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.createTuitionPlan') }}</h1>
                <p class="ym-page-sub">{{ $t('operations.tuitionPlansSubtitle') }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
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
                        <Field class="ym-form-span" :label="$t('operations.description')" :error="form.errors.description">
                            <TextInput v-model="form.description" />
                        </Field>
                    </div>

                    <div class="mt-4">
                        <Checkbox v-model="form.is_active" :label="$t('operations.active')" />
                    </div>
                </div>

                <div class="ym-form-foot">
                    <Link :href="endpoints.index" class="ym-btn ym-btn--quiet">{{ $t('common.cancel') }}</Link>
                    <button type="submit" class="ym-btn ym-btn--primary" :disabled="form.processing">
                        {{ $t('common.create') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</template>
