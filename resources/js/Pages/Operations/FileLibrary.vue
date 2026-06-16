<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const folders = [
    { name: 'Teacher Materials', files: 312, size: '11.3 GB', owner: 'Academic Team' },
    { name: 'Class Promo Images', files: 438, size: '15.8 GB', owner: 'Marketing Team' },
    { name: 'Student Documents', files: 289, size: '6.2 GB', owner: 'Front Desk' },
    { name: 'Workshop Assets', files: 247, size: '5.1 GB', owner: 'Operations' },
];

const uploads = [
    { file: 'spring-retreat-poster.jpg', by: 'Mia Tran', time: 'Today 10:22', status: 'Approved', statusClass: 'ym-status-pill--started' },
    { file: 'yin-sequence-v2.pdf', by: 'Daniel Park', time: 'Today 09:41', status: 'Pending', statusClass: 'ym-status-pill--pending' },
    { file: 'teacher-headshot-emily.png', by: 'Admin Team', time: 'Yesterday 17:03', status: 'Review', statusClass: '' },
    { file: 'weekend-event-banner.psd', by: 'Marketing', time: 'Yesterday 15:18', status: 'Pending', statusClass: 'ym-status-pill--pending' },
];
</script>

<template>
    <AppLayout title="File & Image Upload Management">
        <div class="ym-stat-strip">
            <div class="ym-stat">
                <p class="ym-stat-label">Storage Used</p>
                <p class="ym-stat-value">38.4 GB</p>
                <p class="ym-stat-note">of 120 GB allocated</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Files Uploaded</p>
                <p class="ym-stat-value">1,286</p>
                <p class="ym-stat-note">+94 this month</p>
            </div>
            <div class="ym-stat">
                <p class="ym-stat-label">Pending Review</p>
                <p class="ym-stat-value">26</p>
                <p class="ym-stat-note">Waiting approval tags</p>
            </div>
        </div>

        <div class="ym-page-cols">
            <div class="ym-pane">
                <div class="ym-pane-head">
                    <div class="ym-pane-title-wrap">
                        <i class="bi bi-folder2-open ym-pane-icon" />
                        <h2 class="ym-pane-title">Library Folders</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="folder in folders" :key="folder.name" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ folder.name }}</p>
                                <p class="ym-row-meta">{{ folder.files }} files · {{ folder.owner }}</p>
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
                        <i class="bi bi-cloud-upload ym-pane-icon" />
                        <h2 class="ym-pane-title">Recent Upload Queue</h2>
                    </div>
                </div>
                <div class="ym-pane-body">
                    <div class="ym-row-list">
                        <div v-for="item in uploads" :key="item.file" class="ym-row">
                            <div class="ym-row-main">
                                <p class="ym-row-title">{{ item.file }}</p>
                                <p class="ym-row-meta">{{ item.by }} · {{ item.time }}</p>
                            </div>
                            <div class="ym-row-aside">
                                <span :class="['ym-status-pill', item.statusClass]">{{ item.status }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="ym-info-row">
                        <i class="bi bi-info-circle ym-info-icon" />
                        <span>Drag-and-drop uploads, permissions, and file processing are planned for a later phase.</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>