<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const isDark = ref(false);
const fabOpen = ref(false);
const flashMessage = ref('');
const showFlash = ref(false);

const page = usePage();
const isDemo = computed(() => page.props.isDemo);

// Show FAB only on dashboard
const showFab = computed(() => {
    const r = route();
    return r.current('dashboard');
});

watch(() => page.props.flash?.error, (msg) => {
    if (msg) {
        flashMessage.value = msg;
        showFlash.value = true;
        setTimeout(() => { showFlash.value = false; }, 4000);
    }
});

// Force data reload on browser back/forward to avoid stale cache
const onPopState = () => {
    router.reload();
};

// Swipe navigation for mobile PWA
const navPages = [
    { pattern: 'dashboard', route: 'dashboard' },
    { pattern: 'transactions.*', route: 'transactions.index' },
    { pattern: 'categories.*', route: 'categories.index' },
    { pattern: 'recurring.*', route: 'recurring.index' },
    { pattern: 'transfer.*', route: 'transfer.create' },
    { pattern: 'budgets.*', route: 'budgets.index' },
    { pattern: 'statistics.*', route: 'statistics.index' },
];

let touchStartX = 0;
let touchStartY = 0;
let touchStartTime = 0;

function onTouchStart(e) {
    if (window.innerWidth >= 640) return; // only mobile
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    touchStartTime = Date.now();
}

function onTouchEnd(e) {
    if (window.innerWidth >= 640) return;
    const touch = e.changedTouches[0];
    const deltaX = touch.clientX - touchStartX;
    const deltaY = touch.clientY - touchStartY;
    const elapsed = Date.now() - touchStartTime;

    // Must be a quick swipe (< 400ms), mostly horizontal, at least 80px
    if (elapsed > 400 || Math.abs(deltaX) < 80 || Math.abs(deltaY) > Math.abs(deltaX) * 0.6) return;

    const r = route();
    const currentIndex = navPages.findIndex(p => r.current(p.pattern));
    if (currentIndex === -1) return;

    if (deltaX < 0 && currentIndex < navPages.length - 1) {
        // Swipe left → next page
        router.visit(route(navPages[currentIndex + 1].route));
    } else if (deltaX > 0 && currentIndex > 0) {
        // Swipe right → previous page
        router.visit(route(navPages[currentIndex - 1].route));
    }
}

onMounted(() => {
    isDark.value = localStorage.getItem('theme') === 'dark';
    applyTheme();
    window.addEventListener('popstate', onPopState);
    document.addEventListener('touchstart', onTouchStart, { passive: true });
    document.addEventListener('touchend', onTouchEnd, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('popstate', onPopState);
    document.removeEventListener('touchstart', onTouchStart);
    document.removeEventListener('touchend', onTouchEnd);
});

function toggleTheme() {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    applyTheme();
}

function applyTheme() {
    document.documentElement.classList.toggle('dark', isDark.value);
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            <nav class="border-b border-gray-100 dark:border-gray-700/60 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg sticky top-0 z-40">
                <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Panel
                                </NavLink>
                                <NavLink :href="route('transactions.index')" :active="route().current('transactions.*')">
                                    Transakcje
                                </NavLink>
                                <NavLink :href="route('categories.index')" :active="route().current('categories.*')">
                                    Kategorie
                                </NavLink>
                                <NavLink :href="route('recurring.index')" :active="route().current('recurring.*')">
                                    Cykliczne
                                </NavLink>
                                <NavLink :href="route('transfer.create')" :active="route().current('transfer.*')">
                                    Transfer
                                </NavLink>
                                <NavLink :href="route('budgets.index')" :active="route().current('budgets.*')">
                                    Budżety
                                </NavLink>
                                <NavLink :href="route('statistics.index')" :active="route().current('statistics.*')">
                                    Statystyki
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                            <button @click="toggleTheme" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                </svg>
                            </button>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Wyloguj</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden gap-2">
                            <button @click="toggleTheme" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                </svg>
                            </button>
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-500 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Mobile menu overlay — completely outside nav, fixed above everything -->
            <Teleport to="body">
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showingNavigationDropdown" class="fixed inset-0 z-50 sm:hidden">
                        <!-- Backdrop -->
                        <div class="absolute inset-0 bg-black/40" @click="showingNavigationDropdown = false"></div>

                        <!-- Menu panel sliding from top -->
                        <div class="absolute top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-2xl rounded-b-2xl overflow-hidden">
                            <!-- Header with close button -->
                            <div class="flex items-center justify-between px-4 h-16 border-b border-gray-100 dark:border-gray-700">
                                <Link :href="route('dashboard')" @click="showingNavigationDropdown = false">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                                <button @click="showingNavigationDropdown = false" class="p-2 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Nav links -->
                            <div class="space-y-1 py-3">
                                <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" @click="showingNavigationDropdown = false">Panel</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('transactions.index')" :active="route().current('transactions.*')" @click="showingNavigationDropdown = false">Transakcje</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('categories.index')" :active="route().current('categories.*')" @click="showingNavigationDropdown = false">Kategorie</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('recurring.index')" :active="route().current('recurring.*')" @click="showingNavigationDropdown = false">Cykliczne</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('transfer.create')" :active="route().current('transfer.*')" @click="showingNavigationDropdown = false">Transfer</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('budgets.index')" :active="route().current('budgets.*')" @click="showingNavigationDropdown = false">Budżety</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('statistics.index')" :active="route().current('statistics.*')" @click="showingNavigationDropdown = false">Statystyki</ResponsiveNavLink>
                            </div>

                            <!-- User section -->
                            <div class="border-t border-gray-200 dark:border-gray-600 py-4">
                                <div class="px-4 mb-3">
                                    <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                                </div>
                                <div class="space-y-1">
                                    <ResponsiveNavLink :href="route('profile.edit')" @click="showingNavigationDropdown = false">Profil</ResponsiveNavLink>
                                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">Wyloguj</ResponsiveNavLink>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </Teleport>

            <header class="bg-white dark:bg-gray-800 shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>

            <!-- Demo banner -->
            <div v-if="isDemo" class="fixed top-16 left-0 right-0 z-30 bg-amber-500 text-amber-950 text-center text-xs sm:text-sm py-1.5 font-medium">
                🔒 Tryb demo — przeglądanie bez możliwości edycji.
                <Link :href="route('register')" class="underline font-bold ml-1">Utwórz konto</Link>
            </div>

            <!-- Flash toast -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                <div v-if="showFlash" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-red-600 text-white px-5 py-3 rounded-xl shadow-xl text-sm font-medium max-w-md text-center" @click="showFlash = false">
                    {{ flashMessage }}
                </div>
            </transition>

            <!-- Mobile FAB -->
            <div v-if="showFab" class="sm:hidden fixed bottom-6 right-6 z-50">
                <!-- Backdrop -->
                <div v-if="fabOpen" class="fixed inset-0 bg-black/20 dark:bg-black/40" @click="fabOpen = false"></div>

                <!-- Sub-buttons -->
                <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                    <div v-if="fabOpen" class="absolute bottom-16 right-0 flex flex-col items-end gap-3 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium px-3 py-1.5 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 whitespace-nowrap">Wydatek</span>
                            <Link :href="route('transactions.create', { type: 'expense' })" @click="fabOpen = false" class="w-12 h-12 rounded-full bg-red-500 hover:bg-red-600 text-white shadow-lg flex items-center justify-center transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            </Link>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium px-3 py-1.5 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 whitespace-nowrap">Przychód</span>
                            <Link :href="route('transactions.create', { type: 'income' })" @click="fabOpen = false" class="w-12 h-12 rounded-full bg-green-500 hover:bg-green-600 text-white shadow-lg flex items-center justify-center transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            </Link>
                        </div>
                    </div>
                </transition>

                <!-- Main FAB button -->
                <button @click="fabOpen = !fabOpen" class="relative z-10 w-14 h-14 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-transform duration-200" style="box-shadow: 0 0 20px 4px rgba(99,102,241,0.4)" :class="{ 'rotate-45': fabOpen }">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
            </div>
        </div>
    </div>
</template>
