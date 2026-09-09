<script setup>
import { inject, onBeforeUnmount, onMounted, ref, useSlots } from 'vue';
import { FilterDraftKey } from '@/composables/useFilters.js';

defineProps({
    search: { type: String, default: null },
    searchPlaceholder: { type: String, default: '' },
    count: { type: Number, default: 0 },
    active: { type: Boolean, default: false },
});

const emit = defineEmits(['update:search', 'reset']);

const slots = useSlots();
const open = ref(false);
const wrap = ref(null);

const draft = inject(FilterDraftKey, null);

const openPanel = () => {
    open.value = true;
    draft?.beginDraft();
};

// Leaving the panel any way other than Apply abandons the staged values.
const cancel = () => {
    if (! open.value) {
        return;
    }

    open.value = false;
    draft?.discardDraft();
};

const applyAndClose = () => {
    open.value = false;
    draft?.commitDraft();
};

const onDocumentClick = (event) => {
    // A control that conditioned itself away is detached by now, not outside.
    if (! event.target.isConnected) {
        return;
    }

    if (open.value && wrap.value && ! wrap.value.contains(event.target)) {
        cancel();
    }
};

const onEscape = (event) => {
    if (event.key === 'Escape') {
        cancel();
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onEscape);
});
</script>

<template>
    <div class="ym-toolbar">
        <label v-if="search !== null" class="ym-toolbar-search">
            <i class="bi bi-search" />
            <input
                :value="search"
                type="search"
                :placeholder="searchPlaceholder || $t('common.search')"
                @input="emit('update:search', $event.target.value)"
            />
        </label>

        <div v-if="slots.default" ref="wrap" class="ym-filter-wrap">
            <button
                type="button"
                class="ym-btn ym-btn--outline"
                :class="{ 'is-open': open }"
                :aria-expanded="open"
                @click="open ? cancel() : openPanel()"
            >
                <i class="bi bi-funnel" />
                {{ $t('common.filters') }}
                <span v-if="count" class="ym-filter-count">{{ count }}</span>
                <i class="bi bi-chevron-down ym-filter-caret" />
            </button>

            <div v-if="open" class="ym-filter-panel">
                <div class="ym-filter-panel-grid">
                    <slot />
                </div>
                <div class="ym-filter-panel-foot">
                    <button v-if="active" type="button" class="ym-btn ym-btn--quiet ym-btn--sm" @click="emit('reset')">
                        {{ $t('common.clearFilters') }}
                    </button>
                    <button type="button" class="ym-btn ym-btn--primary ym-btn--sm" @click="applyAndClose">
                        {{ $t('common.applyFilters') }}
                    </button>
                </div>
            </div>
        </div>

        <slot name="actions" />

        <button v-if="active" type="button" class="ym-btn ym-btn--quiet ym-btn--sm ym-toolbar-clear" @click="emit('reset')">
            <i class="bi bi-x-lg" /> {{ $t('common.clearFilters') }}
        </button>
    </div>
</template>
