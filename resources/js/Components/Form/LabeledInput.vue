<template>
    <div>
        <label :for="id" class="block text-sm text-neutral-700 mb-1">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :value="modelValue"
            @input="onInput"
            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm text-neutral-900 focus:outline-none focus:ring focus:border-blue-300"
        />
        <p v-if="error" class="mt-1 text-sm text-rose-600">{{ error }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    autocomplete: {
        type: String,
        default: 'off',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const onInput = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>
