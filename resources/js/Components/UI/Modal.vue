<script setup>
import { watch, onUnmounted } from 'vue';

const props = defineProps({
    show: Boolean,
    title: String,
    description: String,
});

const emit = defineEmits(['close']);

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) emit('close');
};

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
    if (val) {
        document.addEventListener('keydown', handleEscape);
    } else {
        document.removeEventListener('keydown', handleEscape);
    }
});

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
                            <slot />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>