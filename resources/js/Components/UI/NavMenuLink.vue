<template>
    <Link :href="href" :class="linkClasses">
        <span class="ym-nav-item-content">
            <FontAwesomeIcon v-if="icon" :icon="icon" class="ym-nav-item-icon" />
            <span class="ym-nav-item-text">{{ label }}</span>
        </span>
        <span v-if="badge && variant === 'sidebar'" class="ym-side-link-badge">{{ badge }}</span>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

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
        type: [Array, Object, String],
        default: null,
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
</script>
