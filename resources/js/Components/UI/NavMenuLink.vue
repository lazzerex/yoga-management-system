<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const emit = defineEmits(['tab-click']);

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
    iconColor: {
        type: String,
        default: '',
    },
    active: {
        type: Boolean,
        default: false,
    },
    badge: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'sidebar',
    },
});

const linkClasses = computed(() => {
    if (props.variant === 'top') {
        return ['ym-top-link', { 'ym-top-link--active': props.active }];
    }

    return ['ym-side-link', { 'ym-side-link--active': props.active }];
});

const iconStyles = computed(() => {
    if (props.variant === 'sidebar' && props.iconColor) {
        return { '--ym-nav-icon-color': props.iconColor };
    }

    return null;
});
</script>

<template>
    <Link v-if="href" :href="href" :class="linkClasses">
        <span class="ym-nav-item-content">
            <i v-if="icon" :class="['bi', icon, 'ym-nav-item-icon']" :style="iconStyles" />
            <span class="ym-nav-item-text">{{ label }}</span>
        </span>
        <span v-if="badge && variant === 'sidebar'" class="ym-side-link-badge">{{ badge }}</span>
    </Link>
    <span v-else :class="linkClasses" @click="emit('tab-click')">
        <span class="ym-nav-item-content">
            <i v-if="icon" :class="['bi', icon, 'ym-nav-item-icon']" :style="iconStyles" />
            <span class="ym-nav-item-text">{{ label }}</span>
        </span>
    </span>
</template>
