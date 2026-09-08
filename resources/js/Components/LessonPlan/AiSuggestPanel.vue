<script setup>
import { ref } from 'vue';

const props = defineProps({
    endpoint: String,
    payload: Object,
});

const emit = defineEmits(['apply']);

const loading = ref(false);
const steps = ref([]);
const error = ref('');
const applied = ref(false);

const ask = async () => {
    loading.value = true;
    error.value = '';
    steps.value = [];
    applied.value = false;

    try {
        const { data } = await window.axios.post(props.endpoint, props.payload);

        if (data.error) {
            error.value = data.error;
        } else {
            steps.value = data.steps;
        }
    } catch (e) {
        error.value = e.response?.status === 429 ? 'operations.aiThrottled' : 'operations.aiUnavailable';
    } finally {
        loading.value = false;
    }
};

// The plan stores one text column, so the structured steps collapse back to lines
// the coach can edit by hand once they land in the textarea.
const apply = () => {
    emit('apply', steps.value.map((step, index) => {
        const head = `${index + 1}. ${step.name} (${step.sanskrit}) - ${step.duration}`;

        return step.cue ? `${head}\n   ${step.cue}` : head;
    }).join('\n'));

    applied.value = true;
};
</script>

<template>
    <section class="ym-card ym-ai-card">
        <div class="ym-card-head">
            <h2 class="ym-card-title">
                <span class="ym-ai-mark"><i class="bi bi-stars" /></span>
                {{ $t('operations.aiSuggest') }}
            </h2>
            <button type="button" class="ym-btn ym-btn--outline ym-btn--sm" :disabled="loading" @click="ask">
                {{ steps.length ? $t('operations.aiTryAgain') : $t('operations.aiGenerate') }}
            </button>
        </div>

        <div class="ym-card-body ym-stack">
            <p class="ym-ai-sub">{{ $t('operations.aiSuggestIntro') }}</p>

            <div v-if="loading" class="ym-ai-state">
                <p class="ym-ai-status"><span class="ym-ai-pulse" />{{ $t('operations.aiThinkingSequence') }}</p>
                <div class="ym-ai-skeleton">
                    <span v-for="n in 4" :key="n" class="ym-ai-skeleton-row" :style="{ '--ym-stagger': `${n * 90}ms` }" />
                </div>
            </div>

            <p v-else-if="error" class="ym-ai-error ym-reveal">
                <i class="bi bi-exclamation-triangle" />
                <span>{{ $t(error) }}</span>
            </p>

            <div v-else-if="steps.length" class="ym-ai-state ym-reveal">
                <p class="ym-ai-note">
                    <i class="bi bi-robot" />
                    <span>{{ $t('operations.aiDraftNote') }}</span>
                </p>

                <ol class="ym-ai-steps">
                    <li
                        v-for="(step, index) in steps"
                        :key="index"
                        class="ym-ai-step ym-stagger-item"
                        :style="{ '--ym-stagger': `${index * 45}ms` }"
                    >
                        <span class="ym-ai-step-num">{{ index + 1 }}</span>
                        <div class="ym-ai-step-body">
                            <p class="ym-ai-step-name">
                                {{ step.name }}
                                <span v-if="step.sanskrit" class="ym-ai-step-sanskrit">{{ step.sanskrit }}</span>
                            </p>
                            <p v-if="step.cue" class="ym-ai-step-cue">{{ step.cue }}</p>
                        </div>
                        <span v-if="step.duration" class="ym-ai-step-dur">{{ step.duration }}</span>
                    </li>
                </ol>

                <div class="ym-ai-foot">
                    <button type="button" class="ym-btn ym-btn--primary ym-btn--sm" @click="apply">
                        <i class="bi bi-box-arrow-in-down" /> {{ $t('operations.aiUseSuggestion') }}
                    </button>
                    <span v-if="applied" class="ym-ai-applied">
                        <i class="bi bi-check-lg" /> {{ $t('operations.aiApplied') }}
                    </span>
                    <span class="ym-ai-count">{{ $t('operations.aiStepCount', { count: steps.length }) }}</span>
                </div>
            </div>
        </div>
    </section>
</template>
