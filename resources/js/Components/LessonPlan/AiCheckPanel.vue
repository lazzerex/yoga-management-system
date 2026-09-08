<script setup>
import { computed, ref } from 'vue';
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
const error = ref('');
const sendImage = ref(false);

const images = computed(() => props.attachments.filter((file) => file.is_image));
const imageOptions = computed(() => images.value.map((file) => ({ value: String(file.id), label: file.name })));
const selectedMediaId = ref(images.value[0] ? String(images.value[0].id) : '');

// Unticked on every visit by design: an image leaves this system only on a fresh, explicit tick.
const check = async () => {
    loading.value = true;
    error.value = '';
    sections.value = [];

    try {
        const { data } = await window.axios.post(props.endpoint, {
            media_id: sendImage.value ? selectedMediaId.value : null,
        });

        if (data.error) {
            error.value = data.error;
        } else {
            sections.value = data.sections;
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
            <button
                type="button"
                class="ym-btn ym-btn--outline ym-btn--sm"
                :disabled="loading || (sendImage && !selectedMediaId)"
                @click="check"
            >
                {{ sections.length ? $t('operations.aiTryAgain') : $t('operations.aiCheckPlan') }}
            </button>
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

            <ul v-else-if="sections.length" class="ym-ai-findings">
                <li
                    v-for="(section, index) in sections"
                    :key="index"
                    class="ym-ai-finding ym-stagger-item"
                    :class="`is-${section.severity}`"
                    :style="{ '--ym-stagger': `${index * 70}ms` }"
                >
                    <p class="ym-ai-finding-head">
                        <i class="bi" :class="SEVERITY_ICONS[section.severity] ?? 'bi-info-circle'" />
                        <span>{{ section.heading }}</span>
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
        </div>
    </section>
</template>
