<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Doughnut, Bar } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { ref, onMounted, onUnmounted } from 'vue';

ChartJS.register(ArcElement, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    monthlyStats: Object,
    recentTransactions: Array,
    expensesByCategory: Array,
    monthlyTrend: Array,
    currentMonth: String,
    cashBalance: Number,
    cardBalance: Number,
    totalBalance: Number,
});

const deferredPrompt = ref(null);
const showInstallCta = ref(false);

function onBeforeInstallPrompt(e) {
    e.preventDefault();
    deferredPrompt.value = e;
    showInstallCta.value = true;
}

async function installPwa() {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        showInstallCta.value = false;
    }
    deferredPrompt.value = null;
}

function dismissInstallCta() {
    showInstallCta.value = false;
    deferredPrompt.value = null;
}

onMounted(() => {
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
});

const monthNames = ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'];

const doughnutData = {
    labels: props.expensesByCategory.map(c => c.name),
    datasets: [{
        data: props.expensesByCategory.map(c => c.total),
        backgroundColor: props.expensesByCategory.map(c => c.color),
        borderWidth: 2,
        borderColor: '#fff',
    }],
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } },
    },
};

const barData = {
    labels: props.monthlyTrend.map(m => monthNames[m.month - 1]),
    datasets: [
        {
            label: 'Przychody',
            data: props.monthlyTrend.map(m => m.income),
            backgroundColor: '#10b981',
            borderRadius: 4,
        },
        {
            label: 'Wydatki',
            data: props.monthlyTrend.map(m => m.expenses),
            backgroundColor: '#ef4444',
            borderRadius: 4,
        },
    ],
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('pl-PL') + ' zł' } },
    },
};

function formatMoney(amount) {
    return Number(amount).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('pl-PL');
}
</script>

<template>
    <Head title="Panel" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Panel — {{ currentMonth }}</h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- PWA Install CTA -->
                <div v-if="showInstallCta" class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-xl p-4 sm:p-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <div class="text-sm font-semibold text-indigo-800 dark:text-indigo-200">Zainstaluj aplikację</div>
                            <div class="text-xs text-indigo-600 dark:text-indigo-400">Dodaj Mój Budżet do ekranu głównego dla szybkiego dostępu</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="installPwa" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            Zainstaluj
                        </button>
                        <button @click="dismissInstallCta" class="text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 p-1" aria-label="Zamknij">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Monthly Stats -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 sm:mb-3 px-1">Bilans miesięczny</h3>
                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Przychody</div>
                        <div class="text-base sm:text-2xl font-bold text-green-600 mt-0.5">{{ formatMoney(monthlyStats.income) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Wydatki</div>
                        <div class="text-base sm:text-2xl font-bold text-red-600 mt-0.5">{{ formatMoney(monthlyStats.expenses) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Bilans</div>
                        <div class="text-base sm:text-2xl font-bold mt-0.5" :class="monthlyStats.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatMoney(monthlyStats.balance) }}
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Account Balances -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2 sm:mb-3 px-1">Stan konta</h3>
                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5 border-l-4 border-green-500">
                        <div class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="truncate">Gotówka</span>
                        </div>
                        <div class="text-sm sm:text-2xl font-bold mt-1" :class="cashBalance >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatMoney(cashBalance) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5 border-l-4 border-blue-500">
                        <div class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <span class="truncate">Karta</span>
                        </div>
                        <div class="text-sm sm:text-2xl font-bold mt-1" :class="cardBalance >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatMoney(cardBalance) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5 border-l-4 border-indigo-500">
                        <div class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="truncate">Łącznie</span>
                        </div>
                        <div class="text-sm sm:text-2xl font-bold mt-1" :class="totalBalance >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatMoney(totalBalance) }}</div>
                    </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Ostatnie transakcje</h3>
                    <div v-if="recentTransactions.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="t in recentTransactions" :key="t.id" class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ t.category?.icon }}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ t.description || t.category?.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ formatDate(t.date) }}
                                        <span class="ml-2 px-1.5 py-0.5 rounded text-xs" :class="t.payment_method === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'">
                                            {{ t.payment_method === 'card' ? 'Karta' : 'Gotówka' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm font-semibold" :class="t.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                {{ t.type === 'income' ? '+' : '-' }}{{ formatMoney(t.amount) }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-center py-10">Brak transakcji</p>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Wydatki wg kategorii</h3>
                        <div class="h-48 sm:h-64" v-if="expensesByCategory.length > 0">
                            <Doughnut :data="doughnutData" :options="doughnutOptions" />
                        </div>
                        <p v-else class="text-gray-400 text-center py-10">Brak danych</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Trend miesięczny</h3>
                        <div class="h-48 sm:h-64" v-if="monthlyTrend.length > 0">
                            <Bar :data="barData" :options="barOptions" />
                        </div>
                        <p v-else class="text-gray-400 text-center py-10">Brak danych</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
