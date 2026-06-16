<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    type: { type: String, default: 'text' },
    autocomplete: { type: String, default: 'off' },
    placeholder: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <input
        :type="type"
        :value="modelValue"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        class="ym-input"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
