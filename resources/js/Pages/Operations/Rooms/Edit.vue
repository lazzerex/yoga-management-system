<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';

const props = defineProps({
    room: Object,
    branches: Array,
    endpoints: Object,
});

const branchOptions = computed(() => props.branches.map((branch) => ({ value: String(branch.id), label: branch.name })));

const form = useForm({
    branch_id: String(props.room.branch_id),
    name: props.room.name,
    capacity: String(props.room.capacity),
    is_active: props.room.is_active,
});

const submit = () => {
    form.patch(props.endpoints.update);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.editRoom') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.editRoom') }}</h2>
        <p class="ym-subtitle">{{ room.name }}</p>

        <form class="ym-form-grid" @submit.prevent="submit">
            <Field :label="$t('operations.branch')" :error="form.errors.branch_id">
                <Select v-model="form.branch_id" :options="branchOptions" />
            </Field>
            <Field :label="$t('operations.roomName')" :error="form.errors.name">
                <TextInput v-model="form.name" />
            </Field>
            <Field :label="$t('operations.capacity')" :error="form.errors.capacity">
                <TextInput v-model="form.capacity" type="number" />
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
