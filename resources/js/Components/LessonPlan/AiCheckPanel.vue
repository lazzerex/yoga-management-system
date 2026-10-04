<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import Checkbox from '@/Components/Form/Checkbox.vue';
import Select from '@/Components/Form/Select.vue';

const props = defineProps({
    endpoint: String,
    attachments: Array,
});

const SEVERITY_ICONS = {
    ok: 'bi-check-circle',
    note: 'bi-info-circle',
    warn: 'bi-exclamation-triangle',
};

const loading = ref(false);
const sections = ref([]);
const grade = ref(null);
const error = ref('');
const sendImage = ref(false);
const copyState = ref('');

// Presents the model's own number in the three finding tones; it does not re-grade it.
const gradeBand = computed(() => {
    if (grade.value === null) return 'note';
    return grade.value.score >= 80 ? 'ok' : grade.value.score >= 60 ? 'note' : 'warn';
});

const reviewAsText = computed(() => {
    const lines = [];

    if (grade.value) {
        lines.push(`${t('operations.aiGradeTitle')}: ${grade.value.score}/100 - ${grade.value.verdict}`, '');
    }

    sections.value.forEach((section) => {
        lines.push(
            `${section.heading} (${t(`operations.aiSeverity${section.severity}`)})`,
            `${t('operations.aiIssue')}: ${section.issue}`,
            `${t('operations.aiFix')}: ${section.fix}`,
            '',
        );
    });

    return lines.join('\n').trimEnd();
});

let copyTimer = null;

const copy = async () => {
    clearTimeout(copyTimer);

    try {
        await navigator.clipboard.writeText(reviewAsText.value);
        copyState.value = 'ok';
    } catch {
        // Needs a secure context, so a plain-http host refuses it.
        copyState.value = 'fail';
    }

    copyTimer = setTimeout(() => (copyState.value = ''), 2000);
};

onBeforeUnmount(() => clearTimeout(copyTimer));

const images = computed(() => props.attachments.filter((file) => file.is_image));
const imageOptions = computed(() => images.value.map((file) => ({ value: String(file.id), label: file.name })));
const selectedMediaId = ref(images.value[0] ? String(images.value[0].id) : '');

// Unticked on every visit by design: an image leaves this system only on a fresh, explicit tick.
const check = async () => {
    loading.value = true;
    error.value = '';
    sections.value = [];
    grade.value = null;
    copyState.value = '';

    try {
        const { data } = await window.axios.post(props.endpoint, {
            media_id: sendImage.value ? selectedMediaId.value : null,
        });

        if (data.error) {
            error.value = data.error;
        } else {
            sections.value = data.sections;
            grade.value = data.grade ?? null;
        }
    } catch (e) {
        error.value = e.response?.status === 429
            ? 'operations.aiThrottled'
            : (e.response?.data?.error ?? 'operations.aiUnavailable');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <section class="ym-card ym-ai-card">
        <div class="ym-card-head">
            <h2 class="ym-card-title">
                <span class="ym-ai-mark"><i class="bi bi-stars" /></span>
                {{ $t('operations.aiCheckTitle') }}
            </h2>
            <div class="ym-ai-head-actions">
                <button
                    v-if="sections.length"
                    type="button"
                    class="ym-btn ym-btn--outline ym-btn--sm"
                    @click="copy"
                >
                    <i class="bi" :class="copyState === 'ok' ? 'bi-check-lg' : 'bi-clipboard'" />
                    <span v-if="copyState === 'ok'">{{ $t('operations.aiCopied') }}</span>
                    <span v-else-if="copyState === 'fail'">{{ $t('operations.aiCopyFailed') }}</span>
                    <span v-else>{{ $t('operations.aiCopyReview') }}</span>
                </button>
                <button
                    type="button"
                    class="ym-btn ym-btn--outline ym-btn--sm"
                    :disabled="loading || (sendImage && !selectedMediaId)"
                    @click="check"
                >
                    {{ sections.length ? $t('operations.aiTryAgain') : $t('operations.aiCheckPlan') }}
                </button>
            </div>
        </div>

        <div class="ym-card-body ym-stack">
            <p class="ym-ai-note">
                <i class="bi bi-robot" />
                <span>{{ $t('operations.aiCheckNote') }}</span>
            </p>

            <p class="ym-ai-sub">{{ $t('operations.aiCheckIntro') }}</p>

            <div v-if="images.length" class="ym-ai-optin">
                <div class="ym-ai-optin-row">
                    <Checkbox v-model="sendImage" :label="$t('operations.aiSendImage')" />
                    <Select v-if="sendImage" v-model="selectedMediaId" :options="imageOptions" class="ym-ai-optin-pick" />
                </div>
                <p class="ym-note">{{ $t('operations.aiSendImageWarning') }}</p>
            </div>

            <div v-if="loading" class="ym-ai-state">
                <p class="ym-ai-status"><span class="ym-ai-pulse" />{{ $t('operations.aiThinkingCheck') }}</p>
                <div class="ym-ai-skeleton">
                    <span v-for="n in 3" :key="n" class="ym-ai-skeleton-row" :style="{ '--ym-stagger': `${n * 90}ms` }" />
                </div>
            </div>

            <p v-else-if="error" class="ym-ai-error ym-reveal">
                <i class="bi bi-exclamation-triangle" />
                <span>{{ $t(error) }}</span>
            </p>

            <template v-else-if="sections.length">
                <div v-if="grade" class="ym-ai-grade ym-reveal" :class="`is-${gradeBand}`">
                    <p class="ym-ai-grade-score">
                        <span class="ym-ai-grade-num">{{ grade.score }}</span>
                        <span class="ym-ai-grade-den">/ 100</span>
                    </p>
                    <div class="ym-ai-grade-body">
                        <p class="ym-ai-grade-label">{{ $t('operations.aiGradeTitle') }}</p>
                        <p class="ym-ai-grade-verdict">{{ grade.verdict }}</p>
                        <p class="ym-ai-grade-tag">
                            <i class="bi bi-info-circle" />
                            <span>{{ $t('operations.aiGradeAdvisory') }}</span>
                        </p>
                    </div>
                </div>

                <ul class="ym-ai-findings">
                    <li
                        v-for="(section, index) in sections"
                        :key="index"
                        class="ym-ai-finding ym-stagger-item"
                        :class="`is-${section.severity}`"
                        :style="{ '--ym-stagger': `${index * 70}ms` }"
                    >
                        <p class="ym-ai-finding-head">
                            <i class="bi" :class="SEVERITY_ICONS[section.severity] ?? 'bi-info-circle'" />
                            <span class="ym-ai-finding-name">{{ section.heading }}</span>
                            <span class="ym-ai-finding-sev">{{ $t(`operations.aiSeverity${section.severity}`) }}</span>
                        </p>
                        <p class="ym-ai-finding-line">
                            <span class="ym-ai-finding-tag">{{ $t('operations.aiIssue') }}</span>
                            <span>{{ section.issue }}</span>
                        </p>
                        <p class="ym-ai-finding-line">
                            <span class="ym-ai-finding-tag">{{ $t('operations.aiFix') }}</span>
                            <span>{{ section.fix }}</span>
                        </p>
                    </li>
                </ul>
            </template>
        </div>
    </section>
</template>
