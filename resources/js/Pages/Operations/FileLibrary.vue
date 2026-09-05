<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import { formatBytes } from '@/composables/useBytes.js';
import FilterBar from '@/Components/UI/FilterBar.vue';
import DateRange from '@/Components/UI/DateRange.vue';
import SortTh from '@/Components/UI/SortTh.vue';
import { useFilters } from '@/composables/useFilters.js';

const props = defineProps({
    files: Object,
    folders: Array,
    stats: Object,
    filters: Object,
    endpoints: Object,
});

const pendingDelete = ref(null);

const { filters, active, filterCount, reset, toggleSort } = useFilters(props.endpoints.index, props.filters);

const kindLabel = (value) => t(`operations.fileKind${value.charAt(0).toUpperCase()}${value.slice(1)}`);
const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '—');

const toggleFolder = (folderKind) => {
    filters.value.kind = filters.value.kind === folderKind ? '' : folderKind;
};

const confirmDelete = () => {
    router.delete(pendingDelete.value.deleteUrl, {
        preserveScroll: true,
        onSuccess: () => (pendingDelete.value = null),
    });
};
</script>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import { trans as t } from 'laravel-vue-i18n';
export default {
    layout: (h, page) => h(AppLayout, { title: t('operations.fileLibrary') }, () => page),
};
</script>

<template>
    <div class="ym-ui">
        <header class="ym-page-head">
            <div>
                <h1 class="ym-page-title">
                    {{ $t('operations.fileLibrary') }}
                    <span class="ym-count">{{ files.total }}</span>
                </h1>
                <p class="ym-page-sub">{{ $t('operations.fileLibrarySubtitle') }}</p>
            </div>
        </header>

        <div class="ym-stats">
            <div class="ym-stat-card">
                <p class="ym-stat-card-label">{{ $t('operations.storageUsed') }}</p>
                <p class="ym-stat-card-value">{{ formatBytes(stats.totalSize) }}</p>
            </div>
            <div class="ym-stat-card ym-stat-card--info">
                <p class="ym-stat-card-label">{{ $t('operations.filesUploaded') }}</p>
                <p class="ym-stat-card-value">{{ stats.totalFiles }}</p>
            </div>
        </div>

        <section v-if="folders.length" class="ym-card">
            <div class="ym-card-head">
                <h2 class="ym-card-title">{{ $t('operations.libraryFolders') }}</h2>
            </div>
            <div class="ym-card-body">
                <div class="ym-folders">
                    <button
                        v-for="folder in folders"
                        :key="folder.kind"
                        type="button"
                        class="ym-folder-tile"
                        :class="{ 'is-active': filters.kind === folder.kind }"
                        @click="toggleFolder(folder.kind)"
                    >
                        <i :class="filters.kind === folder.kind ? 'bi bi-folder2-open' : 'bi bi-folder'" />
                        <span>
                            <span class="ym-folder-tile-name">{{ kindLabel(folder.kind) }}</span>
                            <span class="ym-folder-tile-meta">{{ folder.count }} · {{ formatBytes(folder.size) }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <section class="ym-card">
            <div class="ym-filter-band">
                <FilterBar
                    v-model:search="filters.search"
                    :count="filterCount"
                    :active="active"
                    @reset="reset"
                >
                    <DateRange v-model:from="filters.from" v-model:to="filters.to" :label="$t('operations.fileUploadedAt')" />
                </FilterBar>
            </div>

            <div class="ym-table-scroll">
                <table class="ym-grid-table">
                    <thead>
                        <tr>
                            <th>{{ $t('operations.filePreview') }}</th>
                            <SortTh field="name" :label="$t('operations.fileName')" :state="filters" @sort="toggleSort" />
                            <th>{{ $t('operations.fileOwner') }}</th>
                            <th>{{ $t('operations.fileKind') }}</th>
                            <SortTh field="size" :label="$t('operations.fileSize')" :state="filters" numeric @sort="toggleSort" />
                            <SortTh field="created_at" :label="$t('operations.fileUploadedAt')" :state="filters" @sort="toggleSort" />
                            <th class="is-actions">{{ $t('operations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="file in files.data" :key="file.id">
                            <td>
                                <img v-if="file.has_thumb" :src="file.thumbUrl" class="ym-thumb" alt="" />
                                <span v-else class="ym-thumb ym-thumb--empty">
                                    <i :class="['bi', file.is_image ? 'bi-image' : 'bi-file-earmark']" />
                                </span>
                            </td>
                            <td class="is-strong">
                                <a :href="file.showUrl" target="_blank">{{ file.name }}</a>
                            </td>
                            <td class="is-muted">{{ file.owner_label }}</td>
                            <td><span class="ym-tag ym-tag--neutral">{{ kindLabel(file.kind) }}</span></td>
                            <td class="is-num is-muted">{{ formatBytes(file.size) }}</td>
                            <td class="is-muted ym-num">{{ formatDate(file.uploaded_at) }}</td>
                            <td class="is-actions">
                                <div class="ym-row-actions">
                                    <a :href="file.downloadUrl" class="ym-btn ym-btn--outline ym-btn--sm">
                                        <i class="bi bi-download" /> {{ $t('operations.fileDownload') }}
                                    </a>
                                    <button
                                        v-if="file.deleteUrl"
                                        type="button"
                                        class="ym-btn ym-btn--danger-quiet ym-btn--sm"
                                        @click="pendingDelete = file"
                                    >
                                        {{ $t('operations.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!files.data.length" class="ym-empty">
                <i class="bi bi-folder2-open" />
                <p>{{ $t('operations.noFiles') }}</p>
            </div>

            <div v-if="files.links.length > 3" class="ym-card-foot">
                <div class="ym-pagination">
                    <Link
                        v-for="link in files.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                        preserve-scroll
                    />
                </div>
            </div>
        </section>

        <Modal :show="!!pendingDelete" :title="$t('operations.deleteFileTitle')" @close="pendingDelete = null">
            <p class="ym-note">{{ $t('operations.confirmDeleteFile', { name: pendingDelete?.name }) }}</p>
            <div class="ym-confirm-modal-actions">
                <button type="button" class="ym-btn ym-btn--outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
                <button type="button" class="ym-btn ym-btn--danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
            </div>
        </Modal>
    </div>
</template>
