<template>
    <AppLayout title="File & Image Upload Management">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Media Repository</h2>
            <p class="ym-subtitle">
                Design blueprint for class materials, event posters, teacher resources, and student-facing documents.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Storage Used</p>
                    <p class="ym-kpi-value">38.4 GB</p>
                    <p class="ym-kpi-note">of 120 GB allocated</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Files Uploaded</p>
                    <p class="ym-kpi-value">1,286</p>
                    <p class="ym-kpi-note">+94 this month</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Pending Review</p>
                    <p class="ym-kpi-value">26</p>
                    <p class="ym-kpi-note">Waiting approval tags</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Library Folders</h3>
                <div class="ym-upload-grid">
                    <article v-for="folder in folders" :key="folder.name" class="ym-upload-card">
                        <p class="ym-upload-title">{{ folder.name }}</p>
                        <p class="ym-upload-meta">{{ folder.files }} files / {{ folder.size }}</p>
                        <p class="ym-upload-meta">Owner: {{ folder.owner }}</p>
                    </article>
                </div>
            </article>

            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Recent Upload Queue</h3>
                <div class="ym-list">
                    <div v-for="item in uploads" :key="item.file" class="ym-list-item">
                        <div>
                            <strong>{{ item.file }}</strong>
                            <p class="ym-list-meta">{{ item.by }} / {{ item.time }}</p>
                        </div>
                        <span :class="['ym-status-pill', item.statusClass]">{{ item.status }}</span>
                    </div>
                </div>

                <p class="ym-note-banner mt-3">
                    <strong>Design Preview:</strong> Drag-and-drop uploads, permissions, and file processing are planned for implementation later.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

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
