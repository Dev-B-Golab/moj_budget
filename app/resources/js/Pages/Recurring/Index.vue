<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    recurringTransactions: Array,
});

function formatMoney(amount) {
    return Number(amount).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('pl-PL');
}

function toggle(id) {
    router.patch(route('recurring.toggle', id));
}

function destroy(id) {
    if (confirm('Czy na pewno chcesz usunąć tę cykliczną transakcję?')) {
        router.delete(route('recurring.destroy', id));
    }
}
</script>

<template>
    <Head title="Cykliczne transakcje" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Cykliczne transakcje</h2>
                <Link :href="route('recurring.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                    + Dodaj cykliczną
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-3 sm:space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="recurringTransactions.length > 0">
                        <!-- Desktop table -->
                        <table class="hidden sm:table w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Opis</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dzień m-ca</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Metoda</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Okres</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Następna</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kwota</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akcje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="r in recurringTransactions" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{ 'opacity-50': !r.is_active }">
                                    <td class="px-4 py-3">
                                        <button @click="toggle(r.id)" class="px-2 py-1 rounded text-xs font-medium" :class="r.is_active ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                                            {{ r.is_active ? 'Aktywna' : 'Wstrzymana' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="inline-flex items-center gap-1">
                                            <span>{{ r.category?.icon }}</span>
                                            <span>{{ r.category?.name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ r.description || '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ r.day_of_month }}.</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs" :class="r.payment_method === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'">
                                            {{ r.payment_method === 'card' ? 'Karta' : 'Gotówka' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ formatDate(r.start_date) }} — {{ r.end_date ? formatDate(r.end_date) : 'bezterminowo' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ r.next_run ? formatDate(r.next_run) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold" :class="r.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                        {{ r.type === 'income' ? '+' : '-' }}{{ formatMoney(r.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <Link :href="route('recurring.edit', r.id)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Edytuj</Link>
                                        <button @click="destroy(r.id)" class="text-red-600 hover:underline text-sm">Usuń</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Mobile cards -->
                        <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="r in recurringTransactions" :key="r.id" class="p-4" :class="{ 'opacity-50': !r.is_active }">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ r.category?.icon }}</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ r.description || r.category?.name }}</div>
                                            <div class="text-xs text-gray-500">Co miesiąc, {{ r.day_of_month }}. dnia</div>
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold" :class="r.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                        {{ r.type === 'income' ? '+' : '-' }}{{ formatMoney(r.amount) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    <span>{{ r.end_date ? formatDate(r.start_date) + ' — ' + formatDate(r.end_date) : 'Bezterminowo' }}</span>
                                    <span>Następna: {{ r.next_run ? formatDate(r.next_run) : '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button @click="toggle(r.id)" class="px-2 py-0.5 rounded text-xs font-medium" :class="r.is_active ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                                            {{ r.is_active ? 'Aktywna' : 'Wstrzymana' }}
                                        </button>
                                        <span class="px-2 py-0.5 rounded text-xs" :class="r.payment_method === 'card' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'">
                                            {{ r.payment_method === 'card' ? 'Karta' : 'Gotówka' }}
                                        </span>
                                    </div>
                                    <div class="space-x-3">
                                        <Link :href="route('recurring.edit', r.id)" class="text-indigo-600 dark:text-indigo-400 text-sm">Edytuj</Link>
                                        <button @click="destroy(r.id)" class="text-red-600 text-sm">Usuń</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12">
                        <p class="text-gray-400 mb-4">Brak cyklicznych transakcji</p>
                        <Link :href="route('recurring.create')" class="text-indigo-600 dark:text-indigo-400 hover:underline">Dodaj pierwszą cykliczną transakcję</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
