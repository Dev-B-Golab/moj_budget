<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    categories: Array,
    filters: Object,
});

const filters = ref({ ...props.filters });

function applyFilters() {
    router.get(route('transactions.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {};
    applyFilters();
}

function deleteTransaction(id) {
    if (confirm('Czy na pewno chcesz usunąć tę transakcję?')) {
        router.delete(route('transactions.destroy', id));
    }
}

function formatMoney(amount) {
    return Number(amount).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('pl-PL');
}

let searchTimeout;
watch(() => filters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
</script>

<template>
    <Head title="Transakcje" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Transakcje</h2>
                <Link :href="route('transactions.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                    + Dodaj
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-3 sm:space-y-4">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 sm:p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
                        <input v-model="filters.search" type="text" placeholder="Szukaj..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm col-span-2 sm:col-span-1" />
                        <select v-model="filters.type" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">Wszystkie typy</option>
                            <option value="expense">Wydatki</option>
                            <option value="income">Przychody</option>
                        </select>
                        <select v-model="filters.category_id" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">Kategoria</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.icon }} {{ cat.name }}</option>
                        </select>
                        <select v-model="filters.payment_method" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            <option value="">Metoda</option>
                            <option value="cash">Gotówka</option>
                            <option value="card">Karta</option>
                        </select>
                        <input v-model="filters.date_from" type="date" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" />
                        <input v-model="filters.date_to" type="date" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" />
                    </div>
                    <div class="mt-2 text-right">
                        <button @click="resetFilters" class="text-xs sm:text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Wyczyść filtry</button>
                    </div>
                </div>

                <!-- Transactions List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="transactions.data.length > 0">
                        <!-- Desktop table -->
                        <table class="hidden sm:table w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Opis</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Metoda</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kwota</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akcje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ formatDate(t.date) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="inline-flex items-center gap-1">
                                            <span>{{ t.category?.icon }}</span>
                                            <span>{{ t.category?.name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ t.description || '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs" :class="t.payment_method === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'">
                                            {{ t.payment_method === 'card' ? 'Karta' : 'Gotówka' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold" :class="t.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                        {{ t.type === 'income' ? '+' : '-' }}{{ formatMoney(t.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <Link :href="route('transactions.edit', t.id)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Edytuj</Link>
                                        <button @click="deleteTransaction(t.id)" class="text-red-600 hover:underline text-sm">Usuń</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Mobile cards -->
                        <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="t in transactions.data" :key="t.id" class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ t.category?.icon }}</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ t.description || t.category?.name }}</div>
                                            <div class="text-xs text-gray-500">{{ formatDate(t.date) }}</div>
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold" :class="t.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                        {{ t.type === 'income' ? '+' : '-' }}{{ formatMoney(t.amount) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded text-xs" :class="t.payment_method === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'">
                                        {{ t.payment_method === 'card' ? 'Karta' : 'Gotówka' }}
                                    </span>
                                    <div class="space-x-3">
                                        <Link :href="route('transactions.edit', t.id)" class="text-indigo-600 dark:text-indigo-400 text-sm">Edytuj</Link>
                                        <button @click="deleteTransaction(t.id)" class="text-red-600 text-sm">Usuń</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-center py-10">Brak transakcji</p>

                    <!-- Pagination -->
                    <div v-if="transactions.links && transactions.last_page > 1" class="flex justify-center gap-1 p-4 border-t border-gray-200 dark:border-gray-700">
                        <template v-for="link in transactions.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'" >{{ link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»') }}</Link>
                            <span v-else class="px-3 py-1 text-sm text-gray-400">{{ link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»') }}</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
