<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed, defineAsyncComponent } from 'vue';
const WalletScene = defineAsyncComponent(() => import('@/Components/WalletScene.vue'));

const demoForm = useForm({});
const loginAsDemo = () => {
    demoForm.post(route('demo.login'));
};

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const isDark = ref(false);
const deferredPrompt = ref(null);
const showInstallButton = ref(false);
const showIOSInstructions = ref(false);

const isIOS = computed(() => {
    if (typeof navigator === 'undefined') return false;
    return /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.userAgent.includes('Mac') && 'ontouchend' in document);
});

const isStandalone = computed(() => {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
});

onMounted(() => {
    isDark.value = localStorage.getItem('theme') === 'dark';
    document.documentElement.classList.toggle('dark', isDark.value);

    // PWA install prompt (Android/Chrome)
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
        showInstallButton.value = true;
    });

    // Show install option for iOS if not already installed
    if (isIOS.value && !isStandalone.value) {
        showInstallButton.value = true;
    }
});

async function installPWA() {
    if (isIOS.value) {
        showIOSInstructions.value = true;
        return;
    }
    if (deferredPrompt.value) {
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        if (outcome === 'accepted') {
            showInstallButton.value = false;
        }
        deferredPrompt.value = null;
    }
}

function toggleTheme() {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    document.documentElement.classList.toggle('dark', isDark.value);
}

const features = [
    { icon: '📊', title: 'Śledzenie wydatków', desc: 'Zapisuj każdy wydatek i przychód. Gotówka czy karta — miej pełną kontrolę nad finansami.' },
    { icon: '📁', title: 'Kategorie', desc: 'Przypisuj transakcje do kategorii. Twórz własne z ikonami i kolorami.' },
    { icon: '📈', title: 'Statystyki', desc: 'Analizuj wydatki w czasie. Wykresy miesięczne, roczne i podział wg kategorii.' },
    { icon: '🌙', title: 'Tryb ciemny', desc: 'Wygodna praca o każdej porze. Przełączaj motyw jednym kliknięciem.' },
    { icon: '📱', title: 'PWA / Mobile', desc: 'Zainstaluj na telefonie jak natywną aplikację. Działa offline.' },
    { icon: '🔒', title: 'Bezpieczeństwo', desc: 'Twoje dane są chronione. Bezpieczne logowanie i szyfrowane hasła.' },
];
</script>

<template>
    <Head title="Mój Budżet — Kontroluj swoje finanse" />

    <div class="min-h-screen overflow-hidden bg-gradient-to-br from-gray-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950 transition-colors duration-300">
        <!-- Nav -->
        <nav class="relative z-20 flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 max-w-7xl mx-auto">
            <div class="flex items-center gap-2 shrink-0">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold text-xs sm:text-sm">MB</span>
                </div>
                <span class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Mój Budżet</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-3">
                <button @click="toggleTheme" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Zmień motyw">
                    <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <Link v-if="canLogin" :href="route('login')" class="hidden sm:inline-flex px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    Zaloguj się
                </Link>
                <Link v-if="canLogin" :href="route('login')" class="sm:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Zaloguj się">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="px-3 py-2 sm:px-5 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/25 transition whitespace-nowrap">
                    Rejestracja
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <section class="relative max-w-7xl mx-auto px-4 sm:px-6 pt-6 pb-12 sm:pt-10 sm:pb-20 lg:pt-16 lg:pb-32">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-sm font-medium mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        Darmowy tracker finansów
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 dark:text-white leading-tight tracking-tight">
                        Kontroluj swoje
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400"> finanse</span>
                    </h1>

                    <p class="mt-4 sm:mt-6 text-base sm:text-lg text-gray-600 dark:text-gray-400 max-w-lg leading-relaxed">
                        Zapisuj wydatki, analizuj trendy i podejmuj lepsze decyzje finansowe. Wszystko w jednym miejscu — na telefonie i komputerze.
                    </p>

                    <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40 transition-all hover:-translate-y-0.5">
                            Zacznij teraz — za darmo
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </Link>
                        <button @click="loginAsDemo" :disabled="demoForm.processing" class="inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 text-sm sm:text-base font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 rounded-xl shadow-sm hover:shadow transition-all disabled:opacity-50">
                            <svg v-if="demoForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            🚀 Wypróbuj demo
                        </button>
                    </div>

                    <!-- PWA Install Button -->
                    <button v-if="showInstallButton && !isStandalone" @click="installPWA" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-700 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Zainstaluj aplikację
                    </button>
                </div>

                <!-- 3D Wallet Scene -->
                <div class="relative h-[280px] sm:h-[400px] lg:h-[500px] overflow-hidden">
                    <Suspense>
                        <WalletScene class="relative z-10" />
                        <template #fallback>
                            <div class="relative z-10 flex items-center justify-center h-full">
                                <div class="coin-loader">
                                    <div class="coin-loader__coin"></div>
                                </div>
                            </div>
                        </template>
                    </Suspense>
                </div>
            </div>
        </section>

        <!-- Stats bar -->
        <section class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 -mt-4 sm:-mt-8 mb-12 sm:mb-20">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-100 dark:border-gray-700 p-4 sm:p-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 text-center">
                    <div>
                        <div class="text-xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">100%</div>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Za darmo</div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">PWA</div>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Jak apka mobilna</div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">📊</div>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Wykresy i analizy</div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">🌙</div>
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Tryb ciemny</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
            <div class="text-center mb-10 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-extrabold text-gray-900 dark:text-white">Wszystko czego potrzebujesz</h2>
                <p class="mt-3 sm:mt-4 text-sm sm:text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Prosty i intuicyjny tracker wydatków z zaawansowanymi funkcjami analitycznymi.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8">
                <div v-for="(f, i) in features" :key="i" class="group bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-2xl p-5 sm:p-7 hover:shadow-xl hover:shadow-indigo-100/50 dark:hover:shadow-indigo-900/30 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all duration-300 hover:-translate-y-1">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-2xl mb-4 group-hover:scale-110 transition-transform">
                        {{ f.icon }}
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ f.title }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ f.desc }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
            <div class="relative bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl sm:rounded-3xl p-6 sm:p-16 text-center overflow-hidden">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMiIvPjwvZz48L2c+PC9zdmc+')] opacity-50"></div>
                <div class="relative z-10">
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white mb-3 sm:mb-4">Zacznij kontrolować swoje finanse</h2>
                    <p class="text-indigo-100 text-sm sm:text-lg mb-6 sm:mb-8 max-w-xl mx-auto">Dołącz i przekonaj się, jak łatwo zarządzać swoim budżetem.</p>
                    <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center px-8 py-4 text-base font-bold text-indigo-600 bg-white hover:bg-gray-50 rounded-xl shadow-xl hover:shadow-2xl transition-all hover:-translate-y-0.5">
                        Utwórz konto za darmo
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10 border-t border-gray-200 dark:border-gray-800">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xs">MB</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Mój Budżet</span>
                </div>
                <p class="text-sm text-gray-400">© {{ new Date().getFullYear() }} Mój Budżet. Wszystkie prawa zastrzeżone.</p>
            </div>
        </footer>

        <!-- iOS PWA Install Instructions Modal -->
        <Teleport to="body">
            <div v-if="showIOSInstructions" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showIOSInstructions = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-w-sm w-full">
                    <button @click="showIOSInstructions = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="text-center">
                        <div class="text-4xl mb-3">📲</div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Zainstaluj na iPhone</h3>
                        <div class="space-y-4 text-left">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-7 h-7 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-sm font-bold">1</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400 pt-0.5">Kliknij ikonę <strong class="text-gray-900 dark:text-white">Udostępnij</strong> <svg class="inline w-5 h-5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg> na dole przeglądarki Safari</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-7 h-7 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-sm font-bold">2</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400 pt-0.5">Przewiń w dół i wybierz <strong class="text-gray-900 dark:text-white">"Dodaj do ekranu początkowego"</strong></p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-7 h-7 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-sm font-bold">3</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400 pt-0.5">Kliknij <strong class="text-gray-900 dark:text-white">"Dodaj"</strong> — gotowe!</p>
                            </div>
                        </div>
                        <button @click="showIOSInstructions = false" class="mt-6 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition">Rozumiem</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.coin-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.coin-loader__coin {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 40%, #d97706 100%);
    box-shadow:
        inset 0 -4px 8px rgba(120, 53, 15, 0.3),
        inset 0 4px 8px rgba(253, 230, 138, 0.4),
        0 8px 32px rgba(251, 191, 36, 0.3);
    position: relative;
    animation: coinSpin 1.2s ease-in-out infinite;
}

.coin-loader__coin::before {
    content: '$';
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 800;
    color: #78350f;
    opacity: 0.8;
}

.coin-loader__coin::after {
    content: '';
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    border: 2px solid rgba(120, 53, 15, 0.2);
}

@keyframes coinSpin {
    0% { transform: rotateY(0deg) scale(1); }
    50% { transform: rotateY(180deg) scale(0.85); }
    100% { transform: rotateY(360deg) scale(1); }
}
</style>
