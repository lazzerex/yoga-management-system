<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    rows: { type: Number, default: 4 },
    placeholder: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <textarea
        :value="modelValue"
        :rows="rows"
        :placeholder="placeholder"
        class="ym-textarea"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>

