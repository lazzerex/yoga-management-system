<script setup>
defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    value: { type: [String, Number, Boolean], required: true },
    label: { type: String, default: '' },
    name: { type: String, required: true },
    ariaLabel: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="ym-radio-wrap">
        <input
            type="radio"
            class="ym-radio"
            :name="name"
            :value="value"
            :checked="modelValue === value"
            :aria-label="!label ? (ariaLabel || undefined) : undefined"
            @change="$emit('update:modelValue', value)"
        />
        <span v-if="label" class="ym-radio-label">{{ label }}</span>
    </label>
</template>