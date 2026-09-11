<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Select from '@/Components/Form/Select.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import MoneyInput from '@/Components/Form/MoneyInput.vue';
import FileInput from '@/Components/Form/FileInput.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Modal from '@/Components/UI/Modal.vue';
import { formatVnd } from '@/composables/useMoney.js';

const props = defineProps({
    invoice: Object,
    methods: Array,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.invoiceStatus${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const formatDate = (value) => new Date(value).toLocaleString();

const statusTone = {
    paid: 'ok',
    waived: 'neutral',
    partial: 'info',
    unpaid: 'warn',
    overdue: 'danger',
};

const settled = computed(() => ['paid', 'waived'].includes(props.invoice.status));

const paidPercent = computed(() => {
    if (!props.invoice.total_amount) {
        return 0;
    }

    return Math.min(100, Math.round((props.invoice.paid_amount / props.invoice.total_amount) * 100));
});

/** Days until the due date: negative once it has passed. */
const daysToDue = computed(() => {
    const due = new Date(`${props.invoice.due_date}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Math.round((due - today) / 86400000);
});

const dueState = computed(() => {
    if (settled.value) {
        return null;
    }
    if (daysToDue.value < 0) {
        return { tone: 'danger', label: t('operations.invoiceOverdueBy', { count: Math.abs(daysToDue.value) }) };
    }
    if (daysToDue.value === 0) {
        return { tone: 'warn', label: t('operations.invoiceDueToday') };
    }

    return { tone: 'neutral', label: t('operations.invoiceDueIn', { count: daysToDue.value }) };
});

const confirmingDelete = ref(false);
const confirmingWaive = ref(false);
const voiding = ref(null);

const methodOptions = props.methods.map((method) => ({
    value: method,
    label: t(`operations.method${method.charAt(0).toUpperCase()}${method.slice(1)}`),
}));

const methodLabel = (method) => t(`operations.method${method.charAt(0).toUpperCase()}${method.slice(1)}`);

const paymentForm = useForm({
    amount: String(props.invoice.balance),
    method: props.methods[0],
    paid_at: new Date().toISOString().slice(0, 10),
    reference: '',
    note: '',
    proof: null,
});

const pay = () => {
    paymentForm.post(props.endpoints.payment, {
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('reference', 'note', 'proof'),
    });
};

const voidForm = useForm({ void_reason: '' });

const confirmVoid = () => {
    voidForm.post(voiding.value.voidUrl, {
        preserveScroll: true,
        onSuccess: () => {
            voiding.value = null;
            voidForm.reset();
        },
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
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ invoice.invoice_number }}
                    <span class="ym-tag" :class="`ym-tag--${statusTone[invoice.status] ?? 'neutral'}`">
                        {{ statusLabel(invoice.status) }}
                    </span>
                </h1>
                <p class="ym-page-sub">{{ invoice.student_name }} · {{ invoice.branch_name }}</p>
            </div>
            <div class="ym-page-actions">
                <a :href="endpoints.pdf" class="ym-btn ym-btn--outline ym-btn--pdf">
                    <i class="bi bi-filetype-pdf" /> {{ $t('common.exportPdf') }}
                </a>
                <button v-if="endpoints.waive" type="button" class="ym-btn ym-btn--outline" @click="confirmingWaive = true">
                    <i class="bi bi-slash-circle" /> {{ $t('operations.waiveInvoice') }}
                </button>
                <button v-if="endpoints.destroy" type="button" class="ym-btn ym-btn--danger-quiet" @click="confirmingDelete = true">
                    <i class="bi bi-trash3" /> {{ $t('operations.delete') }}
                </button>
            </div>
        </header>

        <div class="ym-split">
            <div>
                <section class="ym-card">
                    <div class="ym-card-body">
                        <div class="ym-figure-row">
                            <div>
                                <p class="ym-figure-label">{{ $t('operations.invoiceTotal') }}</p>
                                <p class="ym-figure-value">{{ formatVnd(invoice.total_amount) }}</p>
                            </div>
                            <div>
                                <p class="ym-figure-label">{{ $t('operations.invoicePaid') }}</p>
                                <p class="ym-figure-value ym-figure-value--muted">{{ formatVnd(invoice.paid_amount) }}</p>
                            </div>
                            <div>
                                <p class="ym-figure-label">{{ $t('operations.invoiceBalance') }}</p>
                                <p class="ym-figure-value">{{ formatVnd(invoice.balance) }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span class="ym-progress">
                                <span class="ym-progress-fill" :style="{ width: `${paidPercent}%` }" />
                            </span>
                            <p class="ym-progress-note">
                                {{ $t('operations.invoicePaidOfTotal', { paid: formatVnd(invoice.paid_amount), total: formatVnd(invoice.total_amount) }) }}
                                · {{ paidPercent }}%
                            </p>
                        </div>
                    </div>

                    <div class="ym-card-foot">
                        <dl class="ym-kv">
                            <div>
                                <dt>{{ $t('operations.invoiceIssuedAt') }}</dt>
                                <dd class="ym-num">{{ invoice.issued_at }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.dueDate') }}</dt>
                                <dd class="ym-num">{{ invoice.due_date }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.student') }}</dt>
                                <dd>{{ invoice.student_name }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.planBranch') }}</dt>
                                <dd>{{ invoice.branch_name }}</dd>
                            </div>
                        </dl>
                        <p v-if="invoice.note" class="ym-callout mt-3">
                            <i class="bi bi-sticky" />
                            <span>{{ invoice.note }}</span>
                        </p>
                    </div>
                </section>

                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('operations.invoiceItems') }}</h2>
                    </div>
                    <div class="ym-card-body ym-card-body--flush ym-table-scroll">
                        <table class="ym-grid-table">
                            <thead>
                                <tr>
                                    <th>{{ $t('operations.itemDescription') }}</th>
                                    <th class="is-num">{{ $t('operations.itemQuantity') }}</th>
                                    <th class="is-num">{{ $t('operations.itemUnitPrice') }}</th>
                                    <th class="is-num">{{ $t('operations.itemLineTotal') }}</th>
                                    <th>{{ $t('operations.itemValidUntil') }}</th>
                                    <th class="is-num">{{ $t('operations.itemSessions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in invoice.items" :key="item.id">
                                    <td class="is-strong">{{ item.description }}</td>
                                    <td class="is-num">{{ item.quantity }}</td>
                                    <td class="is-num">{{ formatVnd(item.unit_price) }}</td>
                                    <td class="is-num is-strong">{{ formatVnd(item.line_total) }}</td>
                                    <td class="is-muted">{{ item.valid_until ?? '—' }}</td>
                                    <td class="is-num is-muted">{{ item.sessions_granted ?? '—' }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">{{ $t('operations.invoiceTotal') }}</td>
                                    <td class="is-num">{{ formatVnd(invoice.total_amount) }}</td>
                                    <td colspan="2" />
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('operations.paymentHistory') }}</h2>
                        <span v-if="invoice.payments.length" class="ym-tag ym-tag--neutral">{{ invoice.payments.length }}</span>
                    </div>

                    <ul v-if="invoice.payments.length" class="ym-timeline">
                        <li
                            v-for="payment in invoice.payments"
                            :key="payment.id"
                            class="ym-timeline-item"
                            :class="{ 'is-void': payment.status === 'voided' }"
                        >
                            <span class="ym-timeline-mark">
                                <i :class="payment.status === 'voided' ? 'bi bi-x-lg' : 'bi bi-check-lg'" />
                            </span>
                            <div class="ym-timeline-body">
                                <div class="ym-timeline-row">
                                    <span class="ym-timeline-amount">{{ formatVnd(payment.amount) }}</span>
                                    <div class="ym-timeline-actions">
                                        <a
                                            v-if="payment.proofUrl"
                                            :href="payment.proofUrl"
                                            target="_blank"
                                            class="ym-btn ym-btn--quiet ym-btn--sm"
                                            :title="$t('operations.viewProof')"
                                        >
                                            <i class="bi bi-paperclip" /> {{ $t('operations.viewProof') }}
                                        </a>
                                        <button
                                            v-if="payment.voidUrl"
                                            type="button"
                                            class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                            @click="voiding = payment"
                                        >
                                            {{ $t('operations.voidPayment') }}
                                        </button>
                                    </div>
                                </div>
                                <p class="ym-timeline-meta">
                                    {{ methodLabel(payment.method) }}
                                    · {{ formatDate(payment.paid_at) }}
                                    · {{ payment.recorded_by ?? '—' }}
                                    <template v-if="payment.reference"> · {{ payment.reference }}</template>
                                </p>
                                <p v-if="payment.status === 'voided'" class="ym-timeline-note ym-timeline-note--void">
                                    {{ $t('operations.paymentVoidedOn', { date: formatDate(payment.voided_at), name: payment.voided_by ?? '—' }) }}
                                    — {{ payment.void_reason }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="ym-empty">
                        <i class="bi bi-receipt" />
                        <p>{{ $t('operations.noPayments') }}</p>
                    </div>
                </section>
            </div>

            <aside class="ym-split-rail">
                <section class="ym-card ym-card--accent">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ settled ? $t('operations.invoiceStatusPaid') : $t('operations.amountDue') }}</h2>
                        <span v-if="dueState" class="ym-tag" :class="`ym-tag--${dueState.tone}`">{{ dueState.label }}</span>
                    </div>

                    <div class="ym-card-body">
                        <p class="ym-hero-label">{{ $t('operations.invoiceBalance') }}</p>
                        <p class="ym-hero-value" :class="{ 'ym-hero-value--settled': settled }">
                            {{ formatVnd(invoice.balance) }}
                        </p>

                        <template v-if="endpoints.payment">
                            <form class="ym-stack mt-4" @submit.prevent="pay">
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
                                <Field
                                    :label="$t('operations.paymentProof')"
                                    :error="paymentForm.errors.proof"
                                    :hint="$t('operations.paymentProofHint')"
                                >
                                    <FileInput v-model="paymentForm.proof" accept="application/pdf,image/jpeg,image/png" />
                                </Field>
                                <button type="submit" class="ym-btn ym-btn--primary ym-btn--block" :disabled="paymentForm.processing">
                                    <i class="bi bi-cash-coin" /> {{ $t('operations.recordPayment') }}
                                </button>
                            </form>
                        </template>

                        <p v-else class="ym-callout ym-callout--ok mt-4">
                            <i class="bi bi-check-circle" />
                            <span>{{ $t('operations.invoiceSettledNote') }}</span>
                        </p>
                    </div>
                </section>
            </aside>
        </div>

        <Modal :show="!!voiding" :title="$t('operations.voidPaymentTitle')" @close="voiding = null">
            <p class="ym-note">{{ $t('operations.confirmVoidPayment', { amount: formatVnd(voiding?.amount ?? 0) }) }}</p>
            <Field :label="$t('operations.voidReason')" :error="voidForm.errors.void_reason">
                <Textarea v-model="voidForm.void_reason" />
            </Field>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="voiding = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" :disabled="voidForm.processing" @click="confirmVoid">
                    {{ $t('operations.voidPayment') }}
                </button>
            </div>
        </Modal>

        <Modal :show="confirmingWaive" :title="$t('operations.waiveInvoiceTitle')" @close="confirmingWaive = false">
            <p class="ym-note">{{ $t('operations.confirmWaiveInvoice', { number: invoice.invoice_number }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="confirmingWaive = false">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--primary" @click="waive">{{ $t('common.confirm') }}</button>
            </div>
        </Modal>

        <Modal :show="confirmingDelete" :title="$t('operations.deleteInvoiceTitle')" @close="confirmingDelete = false">
            <p class="ym-note">{{ $t('operations.confirmDeleteInvoice', { number: invoice.invoice_number }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="confirmingDelete = false">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="destroyInvoice">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
