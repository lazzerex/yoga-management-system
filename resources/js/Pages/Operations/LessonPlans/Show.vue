<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Modal from '@/Components/UI/Modal.vue';
import AiCheckPanel from '@/Components/LessonPlan/AiCheckPanel.vue';
import { formatBytes } from '@/composables/useBytes.js';

const props = defineProps({
    plan: Object,
    reviews: Array,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const levelLabel = (level) => t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`);
const formatDate = (value) => (value ? new Date(value).toLocaleString() : t('operations.planNotSubmitted'));

const statusTone = {
    draft: 'neutral',
    pending: 'warn',
    approved: 'ok',
    rejected: 'danger',
};

const confirmingDelete = ref(false);
const reviewForm = useForm({ action: 'approved', comment: '' });

const submitPlan = () => {
    router.post(props.endpoints.submit, {}, { preserveScroll: true });
};

const destroyPlan = () => {
    router.delete(props.endpoints.destroy, {
        preserveScroll: true,
        onSuccess: () => (confirmingDelete.value = false),
    });
};

const decide = (action) => {
    reviewForm.action = action;
    reviewForm.post(props.endpoints.review, {
        preserveScroll: true,
        onSuccess: () => reviewForm.reset('comment'),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.planDetails') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ plan.title }}
                    <span class="ym-tag" :class="`ym-tag--${statusTone[plan.status] ?? 'neutral'}`">
                        {{ statusLabel(plan.status) }}
                    </span>
                </h1>
                <p class="ym-page-sub">{{ plan.class_type_name }} · {{ plan.branch_name }} · {{ plan.coach_name }}</p>
            </div>
            <div class="ym-page-actions">
                <a :href="endpoints.pdf" class="ym-btn ym-btn--export">
                    <i class="bi bi-filetype-pdf" /> {{ $t('common.exportPdf') }}
                </a>
                <Link v-if="endpoints.edit" :href="endpoints.edit" class="ym-btn ym-btn--outline">
                    <i class="bi bi-pencil" /> {{ $t('operations.edit') }}
                </Link>
                <button v-if="endpoints.destroy" type="button" class="ym-btn ym-btn--danger-quiet" @click="confirmingDelete = true">
                    <i class="bi bi-trash3" /> {{ $t('operations.delete') }}
                </button>
            </div>
        </header>

        <div class="ym-split">
            <div>
                <section class="ym-card">
                    <div class="ym-card-meta">
                        <dl class="ym-kv">
                            <div>
                                <dt>{{ $t('operations.planLevel') }}</dt>
                                <dd>{{ levelLabel(plan.level) }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.planDuration') }}</dt>
                                <dd class="ym-num">{{ plan.duration_minutes }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.planSession') }}</dt>
                                <dd>{{ plan.session_label ?? $t('operations.planSessionNone') }}</dd>
                            </div>
                            <div>
                                <dt>{{ $t('operations.planSubmittedAt') }}</dt>
                                <dd>{{ formatDate(plan.submitted_at) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="ym-card-body">
                        <div class="ym-body-block">
                            <h2 class="ym-body-heading">{{ $t('operations.planObjective') }}</h2>
                            <p class="ym-body-text">{{ plan.objective || '-' }}</p>
                        </div>

                        <div class="ym-body-block">
                            <h2 class="ym-body-heading">{{ $t('operations.planAsanaSequence') }}</h2>
                            <pre class="ym-pre">{{ plan.asana_sequence }}</pre>
                        </div>

                        <div class="ym-body-block">
                            <h2 class="ym-body-heading">{{ $t('operations.planAttachments') }}</h2>
                            <template v-if="plan.attachments.length">
                                <div v-for="attachment in plan.attachments" :key="attachment.id" class="ym-file-line">
                                    <i class="bi bi-paperclip" />
                                    <a :href="attachment.showUrl" target="_blank">{{ attachment.name }}</a>
                                    <span class="ym-file-line-size">{{ formatBytes(attachment.size) }}</span>
                                </div>
                            </template>
                            <p v-else class="ym-note">{{ $t('operations.noAttachments') }}</p>
                        </div>
                    </div>
                </section>

                <AiCheckPanel v-if="endpoints.check" :endpoint="endpoints.check" :attachments="plan.attachments" />

                <section class="ym-card">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('operations.reviewHistory') }}</h2>
                        <span v-if="reviews.length" class="ym-tag ym-tag--neutral">{{ reviews.length }}</span>
                    </div>

                    <ul v-if="reviews.length" class="ym-timeline">
                        <li v-for="review in reviews" :key="review.id" class="ym-timeline-item">
                            <span class="ym-timeline-mark">
                                <i :class="review.action === 'approved' ? 'bi bi-check-lg' : 'bi bi-arrow-counterclockwise'" />
                            </span>
                            <div class="ym-timeline-body">
                                <div class="ym-timeline-row">
                                    <span class="ym-tag" :class="`ym-tag--${statusTone[review.action] ?? 'neutral'}`">
                                        {{ statusLabel(review.action) }}
                                    </span>
                                </div>
                                <p class="ym-timeline-meta">{{ review.reviewer_name ?? '-' }} · {{ formatDate(review.reviewed_at) }}</p>
                                <p v-if="review.comment" class="ym-timeline-note">{{ review.comment }}</p>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="ym-empty">
                        <i class="bi bi-clock-history" />
                        <p>{{ $t('operations.noReviews') }}</p>
                    </div>
                </section>
            </div>

            <aside class="ym-split-rail">
                <section v-if="endpoints.review" class="ym-card ym-card--accent">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('operations.reviewDecision') }}</h2>
                    </div>
                    <div class="ym-card-body ym-stack">
                        <Field
                            :label="$t('operations.reviewComment')"
                            :error="reviewForm.errors.comment"
                            :hint="$t('operations.reviewCommentRequired')"
                        >
                            <Textarea v-model="reviewForm.comment" />
                        </Field>
                        <button
                            type="button"
                            class="ym-btn ym-btn--primary ym-btn--block"
                            :disabled="reviewForm.processing"
                            @click="decide('approved')"
                        >
                            <i class="bi bi-check-lg" /> {{ $t('operations.reviewApprove') }}
                        </button>
                        <button
                            type="button"
                            class="ym-btn ym-btn--danger ym-btn--block"
                            :disabled="reviewForm.processing"
                            @click="decide('rejected')"
                        >
                            {{ $t('operations.reviewReject') }}
                        </button>
                    </div>
                </section>

                <section v-else class="ym-card ym-card--accent">
                    <div class="ym-card-head">
                        <h2 class="ym-card-title">{{ $t('operations.status') }}</h2>
                        <span class="ym-tag" :class="`ym-tag--${statusTone[plan.status] ?? 'neutral'}`">
                            {{ statusLabel(plan.status) }}
                        </span>
                    </div>
                    <div class="ym-card-body ym-stack">
                        <p v-if="plan.status === 'pending'" class="ym-callout ym-callout--warn">
                            <i class="bi bi-hourglass-split" />
                            <span>{{ $t('operations.planEditLocked') }}</span>
                        </p>
                        <p v-else-if="plan.status === 'approved'" class="ym-callout ym-callout--ok">
                            <i class="bi bi-check-circle" />
                            <span>{{ $t('operations.planApprovedLocked') }}</span>
                        </p>

                        <button
                            v-if="endpoints.submit"
                            type="button"
                            class="ym-btn ym-btn--primary ym-btn--block"
                            @click="submitPlan"
                        >
                            <i class="bi bi-send" /> {{ $t('operations.planSubmit') }}
                        </button>
                    </div>
                </section>
            </aside>
        </div>

        <Modal :show="confirmingDelete" :title="$t('operations.deletePlanTitle')" @close="confirmingDelete = false">
            <p class="ym-note">{{ $t('operations.confirmDeletePlan', { title: plan.title }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="confirmingDelete = false">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="destroyPlan">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
