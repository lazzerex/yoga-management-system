<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    classType: Object,
    endpoints: Object,
});

const form = useForm({
    name: props.classType.name,
    description: props.classType.description ?? '',
    is_active: props.classType.is_active,
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editClassType') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editClassType') }}</h2>
        <p class="ym-subtitle">{{ classType.name }}</p>

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
                    {{ $t('operations.saveClassType') }}
                </button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
