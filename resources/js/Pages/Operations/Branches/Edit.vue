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
    <div class="ym-ui ym-form-page">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.editBranch') }}</h1>
                <p class="ym-page-sub">{{ branch.name }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
                        <Field :label="$t('operations.branchName')" :error="form.errors.name">
                            <TextInput v-model="form.name" />
                        </Field>
                        <Field :label="$t('operations.address')" :error="form.errors.address">
                            <TextInput v-model="form.address" />
                        </Field>
                        <Field :label="$t('operations.phone')" :error="form.errors.phone">
                            <TextInput v-model="form.phone" />
                        </Field>
                    </div>

                    <div class="mt-4">
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
