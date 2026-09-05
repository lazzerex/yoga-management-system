<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: [Object, Array], default: null },
    multiple: { type: Boolean, default: false },
    accept: { type: String, default: '' },
    hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);

const chosen = computed(() => {
    if (!props.modelValue) return [];

    return props.multiple ? props.modelValue : [props.modelValue];
});

const pick = (event) => {
    const files = Array.from(event.target.files ?? []);
    emit('update:modelValue', props.multiple ? files : files[0] ?? null);
};

// The native input keeps its own value, so clearing the model is not enough.
const clear = () => {
    if (input.value) input.value.value = '';
    emit('update:modelValue', props.multiple ? [] : null);
};

const size = (bytes) => (bytes > 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);
</script>

<template>
    <div class="ym-file-picker">
        <!-- The native control is the label's own input, so the button is the only visible trigger. -->
        <label class="ym-file-trigger">
            <input
                ref="input"
                type="file"
                class="ym-file-hidden"
                :multiple="multiple"
                :accept="accept"
                @change="pick"
            />
            <span class="ym-btn ym-btn--outline ym-btn--sm">
                <i class="bi bi-paperclip" /> {{ $t('common.chooseFile') }}
            </span>
            <span v-if="!chosen.length" class="ym-file-none">{{ $t('common.noFileChosen') }}</span>
        </label>

        <ul v-if="chosen.length" class="ym-file-chips">
            <li v-for="file in chosen" :key="file.name" class="ym-file-chip">
                <i class="bi bi-file-earmark" />
                <span class="ym-file-chip-name">{{ file.name }}</span>
                <span class="ym-file-chip-size">{{ size(file.size) }}</span>
                <button type="button" class="ym-file-chip-x" :aria-label="$t('common.clear')" @click="clear">
                    <i class="bi bi-x-lg" />
                </button>
            </li>
        </ul>

        <span v-if="hint" class="ym-field-hint">{{ hint }}</span>
    </div>
</template>
