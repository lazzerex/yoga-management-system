<template>
    <AppLayout title="Yoga Center Management">
        <section class="ym-surface ym-section">
            <h2 class="ym-title">Center Overview</h2>
            <p class="ym-subtitle">
                Centralized view of branches, facilities, and daily operations across the yoga center network.
            </p>

            <div class="ym-kpi-grid">
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Active Students</p>
                    <p class="ym-kpi-value">486</p>
                    <p class="ym-kpi-note">+18 this month</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Classes Today</p>
                    <p class="ym-kpi-value">34</p>
                    <p class="ym-kpi-note">5 fully booked</p>
                </article>
                <article class="ym-kpi-card">
                    <p class="ym-kpi-label">Active Teachers</p>
                    <p class="ym-kpi-value">27</p>
                    <p class="ym-kpi-note">2 substitutes assigned</p>
                </article>
            </div>
        </section>

        <section class="ym-grid-split mt-4">
            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Branch Performance</h3>
                <div class="ym-list">
                    <div v-for="branch in branches" :key="branch.name" class="ym-list-item">
                        <div>
                            <strong>{{ branch.name }}</strong>
                            <p class="ym-list-meta">{{ branch.address }}</p>
                        </div>
                        <span class="ym-chip">{{ branch.classes }} classes</span>
                    </div>
                </div>
            </article>

            <article class="ym-surface ym-section">
                <h3 class="ym-subsection-title">Facility Notices</h3>
                <div class="ym-list">
                    <div v-for="notice in notices" :key="notice.title" class="ym-list-item">
                        <div>
                            <strong>{{ notice.title }}</strong>
                            <p class="ym-list-meta">{{ notice.meta }}</p>
                        </div>
                        <span class="ym-tag">{{ notice.priority }}</span>
                    </div>
                </div>
                <p class="ym-note-banner mt-3">
                    <strong>Design Preview:</strong> This page is currently a visual module only and will be wired to live center data in a later phase.
                </p>
            </article>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const branches = [
    { name: 'Downtown Studio', address: '1 Main St', classes: 12 },
    { name: 'Westside Branch', address: '28 Lake Ave', classes: 9 },
    { name: 'Riverside Center', address: '19 River Blvd', classes: 7 },
    { name: 'Uptown Loft', address: '53 Summit Rd', classes: 6 },
];

const notices = [
    { title: 'Mat storage renovation', meta: 'Downtown Studio / Apr 22', priority: 'High' },
    { title: 'HVAC maintenance window', meta: 'Westside Branch / Apr 24', priority: 'Medium' },
    { title: 'Equipment restock', meta: 'All branches / Apr 26', priority: 'Normal' },
];
</script>
