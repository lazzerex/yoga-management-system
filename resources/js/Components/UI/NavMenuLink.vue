<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

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

// Inertia keys its prefetch cache on the visit params, headers included, but not on who
// is signed in. Without this, a page prefetched on hover by one account is replayed to the
// next account signed in on this browser, auth props and all. Stamping the viewer's id
// makes entries from another account unmatchable.
const page = usePage();
const prefetchHeaders = computed(() => ({ 'X-Viewer-Id': String(page.props.auth?.user?.id ?? 'guest') }));

const iconStyles = computed(() => {
    if (props.variant === 'sidebar' && props.iconColor) {
        return { '--ym-nav-icon-color': props.iconColor };
    }

    return null;
});
</script>

<template>
    <Link v-if="href" :href="href" :class="linkClasses" prefetch="hover" :headers="prefetchHeaders">
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
