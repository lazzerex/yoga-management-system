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
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">{{ $t('operations.createInvoice') }}</h1>
                <p class="ym-page-sub">{{ $t('operations.tuitionSubtitle') }}</p>
            </div>
        </header>

        <form @submit.prevent="submit">
            <section class="ym-card">
                <div class="ym-card-head">
                    <h2 class="ym-card-title">{{ $t('operations.invoiceDetails') }}</h2>
                </div>
                <div class="ym-card-body">
                    <div class="ym-form-grid-2">
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
                        <Field class="ym-form-span" :label="$t('operations.invoiceNote')" :error="form.errors.note">
                            <TextInput v-model="form.note" />
                        </Field>
                    </div>
                </div>
            </section>

            <section class="ym-card">
                <div class="ym-card-head">
                    <h2 class="ym-card-title">{{ $t('operations.invoiceItems') }}</h2>
                    <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" @click="addItem">
                        <i class="bi bi-plus-lg" /> {{ $t('operations.addItem') }}
                    </button>
                </div>

                <div class="ym-card-body">
                    <p v-if="form.errors.items" class="ym-field-error">{{ form.errors.items }}</p>

                    <div class="ym-repeater">
                        <div v-for="(item, index) in form.items" :key="index" class="ym-repeater-row">
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
                                <p v-else class="ym-repeater-fixed">{{ formatVnd(planPrice(item.tuition_plan_id)) }}</p>
                            </Field>
                            <div class="ym-repeater-end">
                                <p class="ym-repeater-total">{{ formatVnd(lineTotal(item)) }}</p>
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                    :aria-label="$t('operations.removeItem')"
                                    @click="removeItem(index)"
                                >
                                    <i class="bi bi-trash3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ym-form-foot">
                    <div class="ym-form-foot-lead">
                        <p class="ym-figure-label">{{ $t('operations.invoiceTotal') }}</p>
                        <p class="ym-figure-value">{{ formatVnd(total) }}</p>
                    </div>
                    <Link :href="endpoints.index" class="ym-btn ym-btn--quiet">{{ $t('common.cancel') }}</Link>
                    <button type="submit" class="ym-btn ym-btn--primary" :disabled="form.processing">
                        {{ $t('common.create') }}
                    </button>
                </div>
            </section>
        </form>
    </div>
</template>
