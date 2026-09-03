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
</script>

<template>
    <div class="ym-file-input">
        <input
            ref="input"
            type="file"
            class="ym-file-native"
            :multiple="multiple"
            :accept="accept"
            @change="pick"
        />
        <ul v-if="chosen.length" class="ym-file-chosen">
            <li v-for="file in chosen" :key="file.name">{{ file.name }}</li>
        </ul>
        <button v-if="chosen.length" type="button" class="ym-btn-ghost" @click="clear">
            {{ $t('common.clear') }}
        </button>
        <span v-if="hint" class="ym-field-hint">{{ hint }}</span>
    </div>
</template>
