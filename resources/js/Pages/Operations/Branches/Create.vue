<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    endpoints: Object,
});

const form = useForm({
    name: '',
    address: '',
    phone: '',
    is_active: true,
});

const submit = () => {
    form.post(props.endpoints.store);
};
</script>

<template>
    <AppLayout :title="$t('operations.createBranch')">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">{{ $t('operations.createBranch') }}</h2>
            <p class="ym-subtitle">{{ $t('operations.manageBranches') }}</p>

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
                        {{ $t('operations.createBranch') }}
                    </button>
                    <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
