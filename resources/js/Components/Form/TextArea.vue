<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    rows: { type: Number, default: 4 },
    placeholder: { type: String, default: '' },
    autoResize: { type: Boolean, default: false },
    maxHeight: { type: Number, default: 420 },
});

defineEmits(['update:modelValue']);

const field = ref(null);

// Opt-in: a field that is filled programmatically has no keystroke to grow on, so the
// height is recomputed from the value rather than from input events.
const resize = async () => {
    if (! props.autoResize || ! field.value) {
        return;
    }

    await nextTick();
    field.value.style.height = 'auto';
    field.value.style.height = `${Math.min(field.value.scrollHeight, props.maxHeight)}px`;
};

onMounted(resize);
watch(() => props.modelValue, resize);
</script>

<template>
    <textarea
        ref="field"
        :value="modelValue"
        :rows="rows"
        :placeholder="placeholder"
        class="ym-textarea"
        :class="{ 'is-autoresize': autoResize }"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
