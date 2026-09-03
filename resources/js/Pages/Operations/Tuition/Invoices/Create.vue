<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import MoneyInput from '@/Components/Form/MoneyInput.vue';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({
    options: Object,
    selectedBranchId: Number,
    endpoints: Object,
});

const iso = (date) => date.toISOString().slice(0, 10);
const today = iso(new Date());
// Invoices carry a payment term; due == issued would read as overdue from the next day on.
const defaultDue = iso(new Date(Date.now() + 14 * 864e5));

const studentOptions = computed(() => props.options.students.map((student) => ({ value: String(student.id), label: student.name })));
const branchOptions = computed(() => props.options.branches.map((branch) => ({ value: String(branch.id), label: branch.name })));
const planOptions = computed(() => [
    { value: '', label: t('operations.itemFreeText') },
    ...props.options.plans.map((plan) => ({ value: String(plan.id), label: `${plan.name} - ${formatVnd(plan.price_amount)}` })),
]);

const blankItem = () => ({ tuition_plan_id: '', description: '', quantity: '1', unit_price: '0' });

const form = useForm({
    student_profile_id: String(props.options.students[0]?.id ?? ''),
    branch_id: String(props.selectedBranchId ?? props.options.branches[0]?.id ?? ''),
    issued_at: today,
    due_date: defaultDue,
    note: '',
    items: [blankItem()],
});

// The branch switcher reloads props without remounting, so seed the field again by hand.
watch(() => props.selectedBranchId, (branchId) => {
    if (branchId) {
        form.branch_id = String(branchId);
    }
});

const planPrice = (planId) => props.options.plans.find((plan) => String(plan.id) === planId)?.price_amount ?? 0;

const lineTotal = (item) => (item.tuition_plan_id ? planPrice(item.tuition_plan_id) : Number(item.unit_price || 0)) * Number(item.quantity || 0);

const total = computed(() => form.items.reduce((sum, item) => sum + lineTotal(item), 0));

const addItem = () => form.items.push(blankItem());
const removeItem = (index) => form.items.splice(index, 1);

const submit = () => {
    form.post(props.endpoints.store);
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.createInvoice') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <h2 class="ym-title">{{ $t('operations.createInvoice') }}</h2>
        <p class="ym-subtitle">{{ $t('operations.tuitionSubtitle') }}</p>

        <form @submit.prevent="submit">
            <div class="ym-form-grid">
                <Field :label="$t('operations.student')" :error="form.errors.student_profile_id">
                    <Select v-model="form.student_profile_id" :options="studentOptions" />
                </Field>
                <Field :label="$t('operations.planBranch')" :error="form.errors.branch_id">
                    <Select v-model="form.branch_id" :options="branchOptions" />
                </Field>
                <Field :label="$t('operations.invoiceIssuedAt')" :error="form.errors.issued_at">
                    <DatePicker v-model="form.issued_at" />
                </Field>
                <Field :label="$t('operations.dueDate')" :error="form.errors.due_date">
                    <DatePicker v-model="form.due_date" :min="form.issued_at" />
                </Field>
                <Field :label="$t('operations.invoiceNote')" :error="form.errors.note">
                    <TextInput v-model="form.note" />
                </Field>
            </div>

            <h3 class="ym-invoice-heading">{{ $t('operations.invoiceItems') }}</h3>
            <p v-if="form.errors.items" class="ym-field-error">{{ form.errors.items }}</p>

            <div v-for="(item, index) in form.items" :key="index" class="ym-invoice-line">
                <Field :label="$t('operations.itemPlan')" :error="form.errors[`items.${index}.tuition_plan_id`]">
                    <Select v-model="item.tuition_plan_id" :options="planOptions" />
                </Field>
                <Field :label="$t('operations.itemDescription')" :error="form.errors[`items.${index}.description`]">
                    <TextInput v-model="item.description" :disabled="!!item.tuition_plan_id" />
                </Field>
                <Field :label="$t('operations.itemQuantity')" :error="form.errors[`items.${index}.quantity`]">
                    <TextInput v-model="item.quantity" type="number" />
                </Field>
                <Field :label="$t('operations.itemUnitPrice')" :error="form.errors[`items.${index}.unit_price`]">
                    <MoneyInput v-if="!item.tuition_plan_id" v-model="item.unit_price" />
                    <p v-else class="ym-invoice-line-fixed">{{ formatVnd(planPrice(item.tuition_plan_id)) }}</p>
                </Field>
                <div class="ym-invoice-line-end">
                    <p class="ym-invoice-line-total">{{ formatVnd(lineTotal(item)) }}</p>
                    <button
                        v-if="form.items.length > 1"
                        type="button"
                        class="ym-btn-ghost"
                        @click="removeItem(index)"
                    >
                        {{ $t('operations.removeItem') }}
                    </button>
                </div>
            </div>

            <div class="ym-inline-actions">
                <button type="button" class="ym-btn-outline" @click="addItem">{{ $t('operations.addItem') }}</button>
                <p class="ym-invoice-total">{{ $t('operations.invoiceTotal') }}: {{ formatVnd(total) }}</p>
            </div>

            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="form.processing">{{ $t('common.create') }}</button>
                <Link :href="endpoints.index" class="ym-btn-ghost">{{ $t('common.cancel') }}</Link>
            </div>
        </form>
    </section>
</template>
