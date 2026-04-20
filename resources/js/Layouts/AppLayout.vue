<template>
    <div class="ym-shell">
        <aside class="ym-sidebar">
            <div class="ym-brand">
                <p class="ym-brand-mark">YM</p>
                <div>
                    <p class="ym-brand-title">Yoga CRM</p>
                    <p class="ym-brand-subtitle">Management workspace</p>
                </div>
            </div>

            <nav class="ym-side-nav">
                <section v-for="group in sidebarGroups" :key="group.label" class="ym-side-group-wrap">
                    <p class="ym-side-group">{{ group.label }}</p>
                    <NavMenuLink
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        :label="item.label"
                        :icon="item.icon"
                        :badge="item.badge"
                        :active="isActive(item.activePaths)"
                        variant="sidebar"
                    />
                </section>
            </nav>

            <div class="ym-sidebar-footer">
                <span class="ym-badge">{{ roleLabel }}</span>
            </div>
        </aside>

        <div class="ym-workspace">
            <header class="ym-topbar">
                <div class="ym-topbar-row">
                    <div>
                        <p class="ym-overline">Yoga Management System</p>
                        <h1 class="ym-header-title">{{ title }}</h1>
                    </div>

                    <div class="ym-topbar-actions">
                        <label class="ym-search-wrap" aria-label="Search">
                            <FontAwesomeIcon :icon="faMagnifyingGlass" class="ym-search-icon" />
                            <input
                                type="search"
                                class="ym-search"
                                placeholder="Search"
                                aria-label="Search"
                            />
                        </label>
                        <span class="ym-badge">{{ userName }}</span>
                        <button type="button" class="ym-btn-primary" @click="logout">
                            <FontAwesomeIcon :icon="faArrowRightFromBracket" class="ym-btn-icon" />
                            <span>Exit</span>
                        </button>
                    </div>
                </div>

                <nav class="ym-top-links">
                    <NavMenuLink
                        v-for="item in topMenuItems"
                        :key="item.href"
                        :href="item.href"
                        :label="item.label"
                        :icon="item.icon"
                        :active="isActive(item.activePaths)"
                        variant="top"
                    />
                </nav>
            </header>

            <main class="ym-main">
                <div v-if="flash.success" class="ym-alert-success ym-main-alert">{{ flash.success }}</div>
                <div v-if="flash.error" class="ym-alert-error ym-main-alert">{{ flash.error }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faArrowRightFromBracket,
    faBookOpen,
    faBuilding,
    faCalendarCheck,
    faClockRotateLeft,
    faClipboardCheck,
    faFolderOpen,
    faHouse,
    faMagnifyingGlass,
    faMoneyBillWave,
    faSitemap,
    faUserShield,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import NavMenuLink from '../Components/UI/NavMenuLink.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();

const flash = computed(() => page.props.flash ?? {});
const userName = computed(() => page.props.auth?.user?.name ?? 'Guest');
const userRole = computed(() => page.props.auth?.user?.role ?? 'member');
const isAdmin = computed(() => userRole.value === 'admin');
const canAccessPlans = computed(() => ['admin', 'coach'].includes(userRole.value));
const canAccessFees = computed(() => ['admin', 'member'].includes(userRole.value));
const canAccessTeacherOperations = computed(() => ['admin', 'coach'].includes(userRole.value));
const canAccessFileLibrary = computed(() => ['admin', 'coach'].includes(userRole.value));
const roleLabel = computed(() => {
    const role = userRole.value;
    if (!role) return 'guest';
    return role.charAt(0).toUpperCase() + role.slice(1);
});

const isActive = (paths) => {
    const pathList = Array.isArray(paths) ? paths : [paths];
    return pathList.some((path) => (
        page.url === path || page.url.startsWith(`${path}/`) || page.url.startsWith(`${path}?`)
    ));
};

const topMenuItems = computed(() => {
    const baseItems = [
        { label: 'Home', href: '/cms/dashboard', activePaths: ['/cms/dashboard'], icon: faHouse },
        {
            label: 'Ops',
            href: '/cms/operations/yoga-center',
            activePaths: [
                '/cms/operations/yoga-center',
                '/cms/operations/academy',
                '/cms/operations/teacher-attendance',
                '/cms/operations/file-library',
            ],
            icon: faSitemap,
        },
    ];

    if (canAccessPlans.value) {
        baseItems.push({
            label: 'Plans',
            href: '/cms/operations/lesson-planning',
            activePaths: ['/cms/operations/lesson-planning'],
            icon: faBookOpen,
        });
    }

    if (canAccessFees.value) {
        baseItems.push({
            label: 'Fees',
            href: '/cms/operations/tuition-fees',
            activePaths: ['/cms/operations/tuition-fees'],
            icon: faMoneyBillWave,
        });
    }

    if (isAdmin.value) {
        baseItems.push({
            label: 'Admin',
            href: '/cms/admin/users',
            activePaths: ['/cms/admin'],
            icon: faUserShield,
        });
    }

    return baseItems;
});

const sidebarGroups = computed(() => {
    const groups = [
        {
            label: 'Main',
            items: [
                {
                    label: 'Home',
                    href: '/cms/dashboard',
                    activePaths: ['/cms/dashboard'],
                    icon: faHouse,
                },
            ],
        },
        {
            label: 'Operations',
            items: [
                {
                    label: 'Centers',
                    href: '/cms/operations/yoga-center',
                    activePaths: ['/cms/operations/yoga-center'],
                    icon: faBuilding,
                },
                {
                    label: 'Classes',
                    href: '/cms/operations/academy',
                    activePaths: ['/cms/operations/academy'],
                    icon: faUsers,
                },
            ],
        },
    ];

    if (canAccessTeacherOperations.value) {
        groups[1].items.push(
            {
                label: 'Attendance',
                href: '/cms/operations/teacher-attendance',
                activePaths: ['/cms/operations/teacher-attendance'],
                icon: faClipboardCheck,
            },
            {
                label: 'Plans',
                href: '/cms/operations/lesson-planning',
                activePaths: ['/cms/operations/lesson-planning'],
                badge: 'Approval',
                icon: faCalendarCheck,
            },
        );
    }

    if (canAccessFees.value) {
        groups[1].items.push({
            label: 'Tuition',
            href: '/cms/operations/tuition-fees',
            activePaths: ['/cms/operations/tuition-fees'],
            icon: faMoneyBillWave,
        });
    }

    if (canAccessFileLibrary.value) {
        groups[1].items.push({
            label: 'Files',
            href: '/cms/operations/file-library',
            activePaths: ['/cms/operations/file-library'],
            icon: faFolderOpen,
        });
    }

    if (isAdmin.value) {
        groups.push({
            label: 'Admin',
            items: [
                {
                    label: 'Users',
                    href: '/cms/admin/users',
                    activePaths: ['/cms/admin/users'],
                    icon: faUserShield,
                },
                {
                    label: 'Logs',
                    href: '/cms/admin/login-logs',
                    activePaths: ['/cms/admin/login-logs'],
                    icon: faClockRotateLeft,
                },
            ],
        });
    }

    return groups;
});

const logout = () => {
    router.post('/cms/logout');
};
</script>
