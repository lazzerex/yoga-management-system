<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    endpoints: Object,
});

const form = useForm({
    name: '',
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
    layout: (h, page) => h(AppLayout, { title: t('operations.createClassType') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.createClassType') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.manageClassTypes') }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.classTypeName')" :error="form.errors.name">
                <TextInput v-model="form.name" />
            </Field>
            <Field :label="$t('operations.description')" :error="form.errors.description">
                <Textarea v-model="form.description" />
            </Field>
            <Checkbox v-model="form.is_active" :label="$t('operations.active')" />

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">
                    {{ $t('common.create') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
