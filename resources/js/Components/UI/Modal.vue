<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { claimedActionErrors } from '@/composables/useActionError.js';

const props = defineProps({
    show: Boolean,
    title: String,
    description: String,
});

const emit = defineEmits(['close']);

const page = usePage();

// Without this a blocked action's message renders behind the overlay.
const actionError = ref(null);
let errorsAtOpen = null;

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
    if (val) {
        errorsAtOpen = page.props.errors;
        actionError.value = null;
        document.addEventListener('keydown', handleEscape);
    } else {
        document.removeEventListener('keydown', handleEscape);
    }
});

// Fresh errors object per response, so identity separates a new failure from a stale one.
watch(() => page.props.errors, (errors) => {
    if (props.show && errors !== errorsAtOpen) {
        actionError.value = errors?.action ?? null;

        if (actionError.value) {
            claimedActionErrors.value = errors;
        }
    }
});

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) emit('close');
};

onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="ym-modal-fade">
            <div v-if="show" class="ym-modal-overlay" @click.self="$emit('close')">
                <Transition name="ym-modal-pop" appear>
                    <div class="ym-modal" role="dialog" :aria-label="title">
                        <div class="ym-modal-header">
                            <div>
                                <h2 class="ym-modal-title">{{ title }}</h2>
                                <p v-if="description" class="ym-modal-desc">{{ description }}</p>
                            </div>
                            <button type="button" class="ym-modal-close" @click="$emit('close')">&times;</button>
                        </div>
                        <div class="ym-modal-body">
                            <div v-if="actionError" class="ym-alert-error ym-modal-alert" role="alert">
                                <i class="bi bi-exclamation-triangle ym-alert-icon" />
                                <span class="ym-alert-text">{{ actionError }}</span>
                            </div>
                            <slot />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
