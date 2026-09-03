<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Field from '@/Components/Form/Field.vue';
import Textarea from '@/Components/Form/TextArea.vue';
import Modal from '@/Components/UI/Modal.vue';
import { formatBytes } from '@/composables/useBytes.js';

const props = defineProps({
    plan: Object,
    reviews: Array,
    endpoints: Object,
});

const statusLabel = (status) => t(`operations.status${status.charAt(0).toUpperCase()}${status.slice(1)}`);
const levelLabel = (level) => t(`operations.level${level.charAt(0).toUpperCase()}${level.slice(1)}`);
const formatDate = (value) => (value ? new Date(value).toLocaleString() : t('operations.planNotSubmitted'));

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
    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ plan.title }}
                    <span :class="['ym-plan-status', `ym-plan-status--${plan.status}`]">{{ statusLabel(plan.status) }}</span>
                </h2>
                <p class="ym-subtitle">{{ plan.class_type_name }} · {{ plan.branch_name }} · {{ plan.coach_name }}</p>
            </div>
            <div class="ym-inline-actions">
                <Link :href="endpoints.index" class="ym-btn-outline">{{ $t('operations.backToPlans') }}</Link>
                <Link v-if="endpoints.edit" :href="endpoints.edit" class="ym-btn-outline">{{ $t('operations.edit') }}</Link>
                <button v-if="endpoints.submit" type="button" class="ym-btn-sm" @click="submitPlan">
                    {{ $t('operations.planSubmit') }}
                </button>
                <button v-if="endpoints.destroy" type="button" class="ym-btn-danger" @click="confirmingDelete = true">
                    {{ $t('operations.delete') }}
                </button>
            </div>
        </div>

        <p v-if="plan.status === 'pending'" class="ym-card-note">{{ $t('operations.planEditLocked') }}</p>
        <p v-if="plan.status === 'approved'" class="ym-card-note">{{ $t('operations.planApprovedLocked') }}</p>

        <dl class="ym-plan-meta">
            <div>
                <dt>{{ $t('operations.planLevel') }}</dt>
                <dd>{{ levelLabel(plan.level) }}</dd>
            </div>
            <div>
                <dt>{{ $t('operations.planDuration') }}</dt>
                <dd>{{ plan.duration_minutes }}</dd>
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

        <div class="ym-plan-body">
            <h3 class="ym-plan-heading">{{ $t('operations.planObjective') }}</h3>
            <p class="ym-plan-text">{{ plan.objective || '-' }}</p>

            <h3 class="ym-plan-heading">{{ $t('operations.planAsanaSequence') }}</h3>
            <pre class="ym-plan-sequence">{{ plan.asana_sequence }}</pre>

            <h3 class="ym-plan-heading">{{ $t('operations.planAttachments') }}</h3>
            <ul v-if="plan.attachments.length" class="ym-file-list">
                <li v-for="attachment in plan.attachments" :key="attachment.id" class="ym-file-row">
                    <a :href="attachment.showUrl" target="_blank" class="ym-file-name">{{ attachment.name }}</a>
                    <span class="ym-chip">{{ formatBytes(attachment.size) }}</span>
                </li>
            </ul>
            <p v-else class="ym-card-note">{{ $t('operations.noAttachments') }}</p>
        </div>
    </section>

    <section v-if="endpoints.review" class="ym-surface ym-section">
        <h3 class="ym-plan-heading">{{ $t('operations.reviewDecision') }}</h3>
        <Field :label="$t('operations.reviewComment')" :error="reviewForm.errors.comment" :hint="$t('operations.reviewCommentRequired')">
            <Textarea v-model="reviewForm.comment" />
        </Field>
        <div class="ym-inline-actions">
            <button type="button" class="ym-btn-sm" :disabled="reviewForm.processing" @click="decide('approved')">
                {{ $t('operations.reviewApprove') }}
            </button>
            <button type="button" class="ym-btn-danger" :disabled="reviewForm.processing" @click="decide('rejected')">
                {{ $t('operations.reviewReject') }}
            </button>
        </div>
    </section>

    <section class="ym-surface ym-section">
        <h3 class="ym-plan-heading">{{ $t('operations.reviewHistory') }}</h3>
        <ul v-if="reviews.length" class="ym-review-list">
            <li v-for="review in reviews" :key="review.id" class="ym-review-item">
                <span :class="['ym-plan-status', `ym-plan-status--${review.action}`]">{{ statusLabel(review.action) }}</span>
                <div class="ym-review-body">
                    <p class="ym-review-meta">{{ review.reviewer_name ?? '-' }} · {{ formatDate(review.reviewed_at) }}</p>
                    <p v-if="review.comment" class="ym-plan-text">{{ review.comment }}</p>
                </div>
            </li>
        </ul>
        <p v-else class="ym-card-note">{{ $t('operations.noReviews') }}</p>
    </section>

    <Modal :show="confirmingDelete" :title="$t('operations.deletePlanTitle')" @close="confirmingDelete = false">
        <p class="ym-card-note">{{ $t('operations.confirmDeletePlan', { title: plan.title }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="confirmingDelete = false">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="destroyPlan">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
