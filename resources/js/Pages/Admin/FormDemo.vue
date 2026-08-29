<script setup>
import { reactive, ref } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import TextArea from '@/Components/Form/Textarea.vue';
import Checkbox from '@/Components/Form/Checkbox.vue';
import Radio from '@/Components/Form/Radio.vue';
import Select from '@/Components/Form/Select.vue';
import MultiSelect from '@/Components/Form/MultiSelect.vue';
import ColorPicker from '@/Components/Form/ColorPicker.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import DateTimePicker from '@/Components/Form/DateTimePicker.vue';

const roleOptions = [
    { label: t('admin.roles.admin'), value: 'admin' },
    { label: t('admin.roles.coach'), value: 'coach' },
    { label: t('admin.roles.member'), value: 'member' },
];

const tagOptions = [
    { value: 'yoga', label: t('formDemo.tagYoga') },
    { value: 'pilates', label: t('formDemo.tagPilates') },
    { value: 'meditation', label: t('formDemo.tagMeditation') },
];

const initialForm = () => ({
    name: '',
    bio: '',
    role: 'member',
    active: false,
    gender: '',
    tags: [],
    color: '#4b6694',
    startDate: '',
    startDateTime: '',
});

const form = reactive(initialForm());
const submitted = ref(null);

const submitDemo = () => {
    submitted.value = { ...form, tags: [...form.tags] };
};

const resetDemo = () => {
    Object.assign(form, initialForm());
    submitted.value = null;
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default {
    layout: (h, page) => h(AppLayout, { title: t('formDemo.title') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('formDemo.title') }}</h2>
        <p class="ym-subtitle">{{ $t('formDemo.description') }}</p>

        <form class="ym-form-grid" @submit.prevent="submitDemo">
            <Field :label="$t('formDemo.name')">
                <TextInput v-model="form.name" />
            </Field>

            <Field :label="$t('formDemo.bio')">
                <TextArea v-model="form.bio" :rows="4" />
            </Field>

            <Field :label="$t('formDemo.role')">
                <Select v-model="form.role" :options="roleOptions" />
            </Field>

            <Field :label="$t('formDemo.active')" bare>
                <Checkbox v-model="form.active" :label="$t('formDemo.activeLabel')" />
            </Field>

            <Field :label="$t('formDemo.gender')" bare>
                <div class="ym-radio-group">
                    <Radio v-model="form.gender" name="demo-gender" value="male" :label="$t('formDemo.male')" />
                    <Radio v-model="form.gender" name="demo-gender" value="female" :label="$t('formDemo.female')" />
                </div>
            </Field>

            <Field :label="$t('formDemo.tags')">
                <MultiSelect v-model="form.tags" :options="tagOptions" :placeholder="$t('formDemo.tagsPlaceholder')" />
            </Field>

            <Field :label="$t('formDemo.color')">
                <ColorPicker v-model="form.color" :aria-label="$t('formDemo.color')" />
            </Field>

            <Field :label="$t('formDemo.startDate')">
                <DatePicker v-model="form.startDate" />
            </Field>

            <Field :label="$t('formDemo.startDateTime')">
                <DateTimePicker v-model="form.startDateTime" />
            </Field>

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm">{{ $t('formDemo.submit') }}</button>
                <button type="button" class="ym-btn-ghost" @click="resetDemo">{{ $t('formDemo.reset') }}</button>
            </div>
        </form>

        <div v-if="submitted" class="ym-card-note mt-3">
            <p class="ym-label">{{ $t('formDemo.preview') }}</p>
            <pre class="ym-demo-preview">{{ JSON.stringify(submitted, null, 2) }}</pre>
        </div>
    </section>
</template>
