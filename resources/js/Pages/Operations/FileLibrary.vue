<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const folders = [
    { name: 'Syllabus PDFs', count: 28, size: '180 MB' },
    { name: 'Marketing Assets', count: 94, size: '1.2 GB' },
    { name: 'Student Forms', count: 63, size: '45 MB' },
    { name: 'Staff Photos', count: 31, size: '420 MB' },
];

const recent = [
    { name: 'Ashtanga_Flow_Handout.pdf', type: 'PDF', uploaded: 'Apr 21', size: '2.4 MB', status: 'Approved' },
    { name: 'Spring_Poster_Final.png', type: 'Image', uploaded: 'Apr 20', size: '8.1 MB', status: 'Pending' },
    { name: 'Monthly_Report_Apr.xlsx', type: 'Spreadsheet', uploaded: 'Apr 20', size: '1.1 MB', status: 'Approved' },
];
</script>

<template>
    <AppLayout :title="$t('operations.fileLibrary')">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.storageUsed') }}</p>
                <p class="ym-stat-value">1.8 GB</p>
                <p class="ym-stat-note">{{ $t('operations.ofAllocated') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.filesUploaded') }}</p>
                <p class="ym-stat-value">216</p>
                <p class="ym-stat-note">486 {{ $t('operations.studentsCount') }}</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">{{ $t('operations.pendingReview') }}</p>
                <p class="ym-stat-value">14</p>
                <p class="ym-stat-note">3 {{ $t('operations.waitingOver48h') }}</p>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-folder ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.libraryFolders') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="folder in folders" :key="folder.name" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ folder.name }}</p>
                                <p class="ym-row-meta">{{ folder.count }} files · {{ folder.size }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-chip">{{ folder.size }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-clock-history ym-pane-icon" />
                        <h2 class="ym-pane-title">{{ $t('operations.recentUploads') }}</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="file in recent" :key="file.name" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ file.name }}</p>
                                <p class="ym-row-meta">{{ file.type }} · {{ file.uploaded }} · {{ file.size }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span class="ym-tag">{{ file.status }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>{{ $t('operations.filePlaceholder') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>