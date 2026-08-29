<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: (h, page) => h(AppLayout, { title: page.props.title }, () => page),
};
</script>

<template>
    <div class="ym-pane">
        <div class="ym-pane-head">
            <div class="ym-pane-title-wrap">
                <i class="bi bi-gear ym-pane-icon" />
                <h2 class="ym-pane-title">{{ title }}</h2>
            </div>
        </div>
        <div class="ym-pane-body">
            <div class="ym-info-row">
                <i class="bi bi-info-circle ym-info-icon" />
                <span>Mock page for the submenu example. No real settings here yet.</span>
            </div>
        </div>
    </div>
</template>
