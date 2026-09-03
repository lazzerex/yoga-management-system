<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import MoneyInput from '@/Components/Form/MoneyInput.vue';
import Modal from '@/Components/UI/Modal.vue';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({
    invoice: Object,
    methods: Array,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.invoiceStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const formatDate = (value) => new Date(value).toLocaleString();

const confirmingDelete = ref(false);
const confirmingWaive = ref(false);

const methodOptions = props.methods.map((method) => ({
    value: method,
    label: t(`operations.method${method.charAt(0).toUpperCase()}${method.slice(1)}`),
}));

const paymentForm = useForm({
    amount: String(props.invoice.balance),
    method: props.methods[0],
    paid_at: new Date().toISOString().slice(0, 10),
    reference: '',
    note: '',
});

const pay = () => {
    paymentForm.post(props.endpoints.payment, {
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('reference', 'note'),
    });
};

const waive = () => {
    router.post(props.endpoints.waive, {}, {
        preserveScroll: true,
        onSuccess: () => (confirmingWaive.value = false),
    });
};

const destroyInvoice = () => {
    router.delete(props.endpoints.destroy, {
        preserveScroll: true,
        onSuccess: () => (confirmingDelete.value = false),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.invoiceDetails') }, () => page),
};
</script>


<template>
    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ invoice.invoice_number }}
                    <span :class="['ym-invoice-status', `ym-invoice-status--${invoice.status}`]">{{ statusLabel(invoice.status) }}</span>
                </h2>
                <p class="ym-subtitle">{{ invoice.student_name }} · {{ invoice.branch_name }}</p>
            </div>
            <div class="ym-inline-actions">
                <Link :href="endpoints.index" class="ym-btn-outline">{{ $t('operations.backToInvoices') }}</Link>
                <button v-if="endpoints.waive" type="button" class="ym-btn-outline" @click="confirmingWaive = true">
                    {{ $t('operations.waiveInvoice') }}
                </button>
                <button v-if="endpoints.destroy" type="button" class="ym-btn-danger" @click="confirmingDelete = true">
                    {{ $t('operations.delete') }}
                </button>
            </div>
        </div>

        <dl class="ym-plan-meta">
            <div>
                <dt>{{ $t('operations.invoiceIssuedAt') }}</dt>
                <dd>{{ invoice.issued_at }}</dd>
            </div>
            <div>
                <dt>{{ $t('operations.dueDate') }}</dt>
                <dd>{{ invoice.due_date }}</dd>
            </div>
            <div>
                <dt>{{ $t('operations.invoiceTotal') }}</dt>
                <dd>{{ formatVnd(invoice.total_amount) }}</dd>
            </div>
            <div>
                <dt>{{ $t('operations.invoicePaid') }}</dt>
                <dd>{{ formatVnd(invoice.paid_amount) }}</dd>
            </div>
            <div>
                <dt>{{ $t('operations.invoiceBalance') }}</dt>
                <dd>{{ formatVnd(invoice.balance) }}</dd>
            </div>
        </dl>

        <p v-if="invoice.note" class="ym-card-note">{{ invoice.note }}</p>
    </section>

    <section class="ym-surface ym-section">
        <h3 class="ym-invoice-heading">{{ $t('operations.invoiceItems') }}</h3>
        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.itemDescription') }}</th>
                        <th class="ym-th">{{ $t('operations.itemQuantity') }}</th>
                        <th class="ym-th">{{ $t('operations.itemUnitPrice') }}</th>
                        <th class="ym-th">{{ $t('operations.itemLineTotal') }}</th>
                        <th class="ym-th">{{ $t('operations.itemValidUntil') }}</th>
                        <th class="ym-th">{{ $t('operations.itemSessions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in invoice.items" :key="item.id" class="ym-tr">
                        <td class="ym-td font-medium">{{ item.description }}</td>
                        <td class="ym-td">{{ item.quantity }}</td>
                        <td class="ym-td">{{ formatVnd(item.unit_price) }}</td>
                        <td class="ym-td">{{ formatVnd(item.line_total) }}</td>
                        <td class="ym-td text-neutral-500">{{ item.valid_until ?? '-' }}</td>
                        <td class="ym-td text-neutral-500">{{ item.sessions_granted ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section v-if="endpoints.payment" class="ym-surface ym-section">
        <h3 class="ym-invoice-heading">{{ $t('operations.recordPayment') }}</h3>
        <form class="ym-form-grid" @submit.prevent="pay">
            <Field :label="$t('operations.paymentAmount')" :error="paymentForm.errors.amount">
                <MoneyInput v-model="paymentForm.amount" />
            </Field>
            <Field :label="$t('operations.paymentMethod')" :error="paymentForm.errors.method">
                <Select v-model="paymentForm.method" :options="methodOptions" />
            </Field>
            <Field :label="$t('operations.paymentPaidAt')" :error="paymentForm.errors.paid_at">
                <DatePicker v-model="paymentForm.paid_at" />
            </Field>
            <Field :label="$t('operations.paymentReference')" :error="paymentForm.errors.reference">
                <TextInput v-model="paymentForm.reference" />
            </Field>
            <div class="ym-actions">
                <button type="submit" class="ym-btn-sm" :disabled="paymentForm.processing">{{ $t('common.save') }}</button>
            </div>
        </form>
    </section>

    <section class="ym-surface ym-section">
        <h3 class="ym-invoice-heading">{{ $t('operations.paymentHistory') }}</h3>
        <ul v-if="invoice.payments.length" class="ym-review-list">
            <li v-for="payment in invoice.payments" :key="payment.id" class="ym-review-item">
                <span class="ym-invoice-status ym-invoice-status--paid">{{ formatVnd(payment.amount) }}</span>
                <div class="ym-review-body">
                    <p class="ym-review-meta">
                        {{ $t(`operations.method${payment.method.charAt(0).toUpperCase()}${payment.method.slice(1)}`) }}
                        · {{ formatDate(payment.paid_at) }}
                        · {{ payment.recorded_by ?? '-' }}
                    </p>
                    <p v-if="payment.reference" class="ym-plan-text">{{ payment.reference }}</p>
                </div>
            </li>
        </ul>
        <p v-else class="ym-card-note">{{ $t('operations.noPayments') }}</p>
    </section>

    <Modal :show="confirmingWaive" :title="$t('operations.waiveInvoiceTitle')" @close="confirmingWaive = false">
        <p class="ym-card-note">{{ $t('operations.confirmWaiveInvoice', { number: invoice.invoice_number }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="confirmingWaive = false">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-sm" @click="waive">{{ $t('common.confirm') }}</button>
        </div>
    </Modal>

    <Modal :show="confirmingDelete" :title="$t('operations.deleteInvoiceTitle')" @close="confirmingDelete = false">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteInvoice', { number: invoice.invoice_number }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="confirmingDelete = false">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="destroyInvoice">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
