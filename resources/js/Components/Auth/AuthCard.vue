<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-neutral-100 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-neutral-200 bg-white p-6 shadow">
            <h1 class="text-2xl font-semibold text-neutral-900 mb-6">{{ title }}</h1>
            <slot />
        </div>
    </div>
</template>