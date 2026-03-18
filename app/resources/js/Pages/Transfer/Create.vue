<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    direction: 'card_to_cash',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

function submit() {
    form.post(route('transfer.store'));
}
</script>

<template>
    <Head title="Transfer" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Transfer gotówka / karta</h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Direction -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kierunek</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <button type="button" @click="form.direction = 'card_to_cash'" class="flex-1 py-3 text-center font-medium transition" :class="form.direction === 'card_to_cash' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💳 → 💵 Wypłata
                                </button>
                                <button type="button" @click="form.direction = 'cash_to_card'" class="flex-1 py-3 text-center font-medium transition" :class="form.direction === 'cash_to_card' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💵 → 💳 Wpłata
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ form.direction === 'card_to_cash' ? 'Wypłata gotówki z bankomatu (karta → gotówka)' : 'Wpłata gotówki na konto (gotówka → karta)' }}
                            </p>
                            <div v-if="form.errors.direction" class="text-red-500 text-sm mt-1">{{ form.errors.direction }}</div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kwota (zł)</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-lg" placeholder="0.00" />
                            <div v-if="form.errors.amount" class="text-red-500 text-sm mt-1">{{ form.errors.amount }}</div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Opis (opcjonalnie)</label>
                            <input v-model="form.description" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" :placeholder="form.direction === 'card_to_cash' ? 'Wypłata z bankomatu' : 'Wpłata na konto'" />
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data</label>
                            <input v-model="form.date" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <div v-if="form.errors.date" class="text-red-500 text-sm mt-1">{{ form.errors.date }}</div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4">
                            <Link :href="route('dashboard')" class="text-gray-500 dark:text-gray-400 hover:underline text-sm">Anuluj</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2.5 px-6 rounded-lg transition">
                                Wykonaj transfer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
