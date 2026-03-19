<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Doughnut, Bar } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    monthlyData: Array,
    expensesByCategory: Array,
    incomeByCategory: Array,
    paymentMethodStats: Array,
    yearTotal: Object,
    selectedYear: Number,
    selectedMonth: Number,
    availableYears: Array,
    dailyData: Array,
    dailyTotals: Array,
});

const monthNames = ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'];
const fullMonthNames = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];

function changeYear(year) {
    router.get(route('statistics.index'), { year, month: props.selectedMonth }, { preserveState: true, replace: true });
}

function changeMonth(month) {
    router.get(route('statistics.index'), { year: props.selectedYear, month }, { preserveState: true, replace: true });
}

function formatMoney(amount) {
    return Number(amount).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
}

const barData = computed(() => ({
    labels: monthNames,
    datasets: [
        {
            label: 'Przychody',
            data: monthNames.map((_, i) => {
                const m = props.monthlyData.find(d => d.month === i + 1);
                return m ? Number(m.income) : 0;
            }),
            backgroundColor: '#10b981',
            borderRadius: 4,
        },
        {
            label: 'Wydatki',
            data: monthNames.map((_, i) => {
                const m = props.monthlyData.find(d => d.month === i + 1);
                return m ? Number(m.expenses) : 0;
            }),
            backgroundColor: '#ef4444',
            borderRadius: 4,
        },
    ],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('pl-PL') + ' zł' } },
    },
};

const expenseDoughnutData = computed(() => ({
    labels: props.expensesByCategory.map(c => c.name),
    datasets: [{
        data: props.expensesByCategory.map(c => Number(c.total)),
        backgroundColor: props.expensesByCategory.map(c => c.color),
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const incomeDoughnutData = computed(() => ({
    labels: props.incomeByCategory.map(c => c.name),
    datasets: [{
        data: props.incomeByCategory.map(c => Number(c.total)),
        backgroundColor: props.incomeByCategory.map(c => c.color),
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } },
};

const paymentDoughnutData = computed(() => ({
    labels: props.paymentMethodStats.map(p => p.payment_method === 'card' ? 'Karta' : 'Gotówka'),
    datasets: [{
        data: props.paymentMethodStats.map(p => Number(p.total)),
        backgroundColor: ['#3b82f6', '#22c55e'],
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

// Group daily data by day for the breakdown table
const dailyBreakdown = computed(() => {
    const days = {};
    props.dailyTotals.forEach(d => {
        days[d.day] = { day: d.day, income: Number(d.income), expenses: Number(d.expenses), categories: [] };
    });
    props.dailyData.forEach(d => {
        if (!days[d.day]) {
            days[d.day] = { day: d.day, income: 0, expenses: 0, categories: [] };
        }
        if (d.type === 'expense') {
            days[d.day].categories.push({
                name: d.category_name,
                icon: d.category_icon,
                color: d.category_color,
                total: Number(d.total),
            });
        }
    });
    return Object.values(days).sort((a, b) => b.day - a.day);
});
</script>

<template>
    <Head title="Statystyki" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Statystyki</h2>
                <div class="flex items-center gap-2">
                    <select @change="changeMonth(Number($event.target.value))" :value="selectedMonth" class="rounded-md border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option v-for="(name, i) in fullMonthNames" :key="i" :value="i + 1">{{ name }}</option>
                    </select>
                    <select @change="changeYear(Number($event.target.value))" :value="selectedYear" class="rounded-md border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Year summary cards -->
                <div class="grid grid-cols-3 gap-2 sm:gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Przychody ({{ selectedYear }})</div>
                        <div class="text-base sm:text-2xl font-bold text-green-600 mt-0.5">{{ formatMoney(yearTotal.income) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Wydatki ({{ selectedYear }})</div>
                        <div class="text-base sm:text-2xl font-bold text-red-600 mt-0.5">{{ formatMoney(yearTotal.expenses) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-5">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Bilans ({{ selectedYear }})</div>
                        <div class="text-base sm:text-2xl font-bold mt-0.5" :class="yearTotal.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatMoney(yearTotal.balance) }}
                        </div>
                    </div>
                </div>

                <!-- Monthly chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Przychody vs wydatki miesięcznie</h3>
                    <div class="h-56 sm:h-80">
                        <Bar :data="barData" :options="barOptions" />
                    </div>
                </div>

                <!-- Daily breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">
                        Wydatki dzienne — {{ fullMonthNames[selectedMonth - 1] }} {{ selectedYear }}
                    </h3>
                    <div v-if="dailyBreakdown.length > 0" class="space-y-3">
                        <div v-for="day in dailyBreakdown" :key="day.day" class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ day.day }}.{{ String(selectedMonth).padStart(2, '0') }}.{{ selectedYear }}</span>
                                <div class="flex items-center gap-3 text-sm">
                                    <span v-if="day.income > 0" class="text-green-600 font-medium">+{{ formatMoney(day.income) }}</span>
                                    <span v-if="day.expenses > 0" class="text-red-600 font-medium">-{{ formatMoney(day.expenses) }}</span>
                                </div>
                            </div>
                            <div v-if="day.categories.length > 0" class="flex flex-wrap gap-2">
                                <span v-for="cat in day.categories" :key="cat.name" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: cat.color }"></span>
                                    {{ cat.icon }} {{ cat.name }}: {{ formatMoney(cat.total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-center py-10">Brak danych za ten miesiąc</p>
                </div>

                <!-- Category charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Wydatki wg kategorii</h3>
                        <div class="h-48 sm:h-72" v-if="expensesByCategory.length > 0">
                            <Doughnut :data="expenseDoughnutData" :options="doughnutOptions" />
                        </div>
                        <p v-else class="text-gray-400 text-center py-10">Brak danych</p>
                        <div v-if="expensesByCategory.length > 0" class="mt-4 space-y-2">
                            <div v-for="cat in expensesByCategory" :key="cat.name" class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color }"></div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ cat.icon }} {{ cat.name }}</span>
                                </div>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ formatMoney(cat.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Przychody wg kategorii</h3>
                        <div class="h-48 sm:h-72" v-if="incomeByCategory.length > 0">
                            <Doughnut :data="incomeDoughnutData" :options="doughnutOptions" />
                        </div>
                        <p v-else class="text-gray-400 text-center py-10">Brak danych</p>
                        <div v-if="incomeByCategory.length > 0" class="mt-4 space-y-2">
                            <div v-for="cat in incomeByCategory" :key="cat.name" class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: cat.color }"></div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ cat.icon }} {{ cat.name }}</span>
                                </div>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ formatMoney(cat.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment method stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Metody płatności</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 items-center">
                        <div class="h-48 sm:h-64" v-if="paymentMethodStats.length > 0">
                            <Doughnut :data="paymentDoughnutData" :options="doughnutOptions" />
                        </div>
                        <p v-else class="text-gray-400 text-center py-10">Brak danych</p>
                        <div v-if="paymentMethodStats.length > 0" class="space-y-3">
                            <div v-for="pm in paymentMethodStats" :key="pm.payment_method" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                                <div class="flex items-center gap-2">
                                    <span>{{ pm.payment_method === 'card' ? '💳' : '💵' }}</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ pm.payment_method === 'card' ? 'Karta' : 'Gotówka' }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ formatMoney(pm.total) }}</div>
                                    <div class="text-xs text-gray-500">{{ pm.count }} transakcji</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
