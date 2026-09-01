<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    branch: Object,
    endpoints: Object,
});

const form = useForm({
    name: props.branch.name,
    address: props.branch.address,
    phone: props.branch.phone ?? '',
    is_active: props.branch.is_active,
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editBranch') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editBranch') }}</h2>
        <p class="ym-subtitle">{{ branch.name }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.branchName')" :error="form.errors.name">
                <TextInput v-model="form.name" />
            </Field>
            <Field :label="$t('operations.address')" :error="form.errors.address">
                <TextInput v-model="form.address" />
            </Field>
            <Field :label="$t('operations.phone')" :error="form.errors.phone">
                <TextInput v-model="form.phone" />
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
