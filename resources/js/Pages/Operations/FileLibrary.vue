<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Modal from '@/Components/UI/Modal.vue';
import { formatBytes } from '@/composables/useBytes.js';

const props = defineProps({
    files: Object,
    folders: Array,
    stats: Object,
    filters: Object,
    endpoints: Object,
});

const search = ref(props.filters?.search ?? '');
const kind = ref(props.filters?.kind ?? '');
const pendingDelete = ref(null);

const hasActiveFilters = computed(() => search.value || kind.value);

const kindLabel = (value) => t(`operations.fileKind${value.charAt(0).toUpperCase()}${value.slice(1)}`);
const formatDate = (value) => (value ? new Date(value).toLocaleDateString() : '-');

let searchTimeout = null;

const applyFilters = () => {
    router.get(props.endpoints.index, {
        search: search.value || undefined,
        kind: kind.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

watch(kind, applyFilters);

const resetFilters = () => {
    search.value = '';
    kind.value = '';
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
    <div class="ym-stat-strip">
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.storageUsed') }}</p>
            <p class="ym-stat-value">{{ formatBytes(stats.totalSize) }}</p>
        </div>
        <div class="ym-stat">
            <p class="ym-stat-label">{{ $t('operations.filesUploaded') }}</p>
            <p class="ym-stat-value">{{ stats.totalFiles }}</p>
        </div>
    </div>

    <section v-if="folders.length" class="ym-surface ym-section">
        <h3 class="ym-invoice-heading">{{ $t('operations.libraryFolders') }}</h3>
        <div class="ym-folder-grid">
            <button
                v-for="folder in folders"
                :key="folder.kind"
                type="button"
                :class="['ym-folder', { 'ym-folder--active': kind === folder.kind }]"
                @click="kind = kind === folder.kind ? '' : folder.kind"
            >
                <i class="bi bi-folder ym-pane-icon" />
                <span class="ym-folder-name">{{ kindLabel(folder.kind) }}</span>
                <span class="ym-folder-meta">{{ folder.count }} · {{ formatBytes(folder.size) }}</span>
            </button>
        </div>
    </section>

    <section class="ym-surface ym-section">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="ym-title">
                    {{ $t('operations.fileLibrary') }}
                    <span class="ym-count-badge">{{ files.total }}</span>
                </h2>
                <p class="ym-subtitle">{{ $t('operations.fileLibrarySubtitle') }}</p>
            </div>
            <div class="ym-inline-actions">
                <input v-model="search" type="search" class="ym-input" :placeholder="$t('common.search')" />
                <button v-if="hasActiveFilters" type="button" class="ym-btn-ghost" @click="resetFilters">
                    {{ $t('common.clearFilters') }}
                </button>
            </div>
        </div>

        <div class="ym-table-wrap">
            <table class="ym-table">
                <thead>
                    <tr>
                        <th class="ym-th">{{ $t('operations.filePreview') }}</th>
                        <th class="ym-th">{{ $t('operations.fileName') }}</th>
                        <th class="ym-th">{{ $t('operations.fileOwner') }}</th>
                        <th class="ym-th">{{ $t('operations.fileKind') }}</th>
                        <th class="ym-th">{{ $t('operations.fileSize') }}</th>
                        <th class="ym-th">{{ $t('operations.fileUploadedAt') }}</th>
                        <th class="ym-th">{{ $t('operations.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file in files.data" :key="file.id" class="ym-tr">
                        <td class="ym-td">
                            <img v-if="file.has_thumb" :src="file.thumbUrl" class="ym-avatar-thumb" alt="" />
                            <span v-else class="ym-avatar-thumb ym-avatar-thumb--empty">
                                <i :class="['bi', file.is_image ? 'bi-image' : 'bi-file-earmark']" />
                            </span>
                        </td>
                        <td class="ym-td font-medium">
                            <a :href="file.showUrl" target="_blank" class="ym-file-name">{{ file.name }}</a>
                        </td>
                        <td class="ym-td text-neutral-500">{{ file.owner_label }}</td>
                        <td class="ym-td"><span class="ym-chip">{{ kindLabel(file.kind) }}</span></td>
                        <td class="ym-td text-neutral-500">{{ formatBytes(file.size) }}</td>
                        <td class="ym-td text-neutral-500">{{ formatDate(file.uploaded_at) }}</td>
                        <td class="ym-td">
                            <div class="ym-inline-actions">
                                <a :href="file.downloadUrl" class="ym-btn-outline">{{ $t('operations.fileDownload') }}</a>
                                <button v-if="file.deleteUrl" type="button" class="ym-btn-danger" @click="pendingDelete = file">
                                    {{ $t('operations.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!files.data.length">
                        <td class="ym-td text-neutral-500" colspan="7">{{ $t('operations.noFiles') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="files.links.length > 3" class="ym-pagination">
            <Link
                v-for="link in files.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                :class="['ym-page-link', { 'ym-page-link--active': link.active, 'ym-page-link--disabled': !link.url }]"
                preserve-scroll
            />
        </div>
    </section>

    <Modal :show="!!pendingDelete" :title="$t('operations.deleteFileTitle')" @close="pendingDelete = null">
        <p class="ym-card-note">{{ $t('operations.confirmDeleteFile', { name: pendingDelete?.name }) }}</p>
        <div class="ym-confirm-modal-actions">
            <button type="button" class="ym-btn-outline" @click="pendingDelete = null">{{ $t('common.cancel') }}</button>
            <button type="button" class="ym-btn-danger" @click="confirmDelete">{{ $t('operations.delete') }}</button>
        </div>
    </Modal>
</template>
