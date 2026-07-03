<script setup>
defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    value: { type: [String, Number, Boolean], required: true },
    label: { type: String, default: '' },
    name: { type: String, required: true },
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
            @change="$emit('update:modelValue', value)"
        />
        <span v-if="label" class="ym-radio-label">{{ label }}</span>
    </label>
</template>