<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    recurring: Object,
    categories: Array,
});

const form = useForm({
    type: props.recurring.type,
    amount: props.recurring.amount,
    category_id: props.recurring.category_id,
    payment_method: props.recurring.payment_method,
    description: props.recurring.description || '',
    day_of_month: props.recurring.day_of_month,
    start_date: props.recurring.start_date?.split('T')[0] || '',
    end_date: props.recurring.end_date?.split('T')[0] || '',
    is_active: props.recurring.is_active,
});

const filteredCategories = computed(() => {
    return props.categories.filter(c => c.type === form.type);
});

function submit() {
    form.put(route('recurring.update', props.recurring.id));
}
</script>

<template>
    <Head title="Edytuj cykliczną transakcję" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Edytuj cykliczną transakcję</h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Type toggle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Typ</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <button type="button" @click="form.type = 'expense'; form.category_id = ''" class="flex-1 py-3 text-center font-medium transition" :class="form.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    Wydatek
                                </button>
                                <button type="button" @click="form.type = 'income'; form.category_id = ''" class="flex-1 py-3 text-center font-medium transition" :class="form.type === 'income' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    Przychód
                                </button>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kwota (zł)</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-lg" />
                            <div v-if="form.errors.amount" class="text-red-500 text-sm mt-1">{{ form.errors.amount }}</div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategoria</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <button v-for="cat in filteredCategories" :key="cat.id" type="button" @click="form.category_id = cat.id" class="flex flex-col items-center p-3 rounded-lg border-2 transition text-center" :class="form.category_id === cat.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'" :style="form.category_id === cat.id ? { borderColor: cat.color } : {}">
                                    <span class="text-xl">{{ cat.icon }}</span>
                                    <span class="text-xs mt-1 text-gray-700 dark:text-gray-300 truncate w-full">{{ cat.name }}</span>
                                </button>
                            </div>
                            <div v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</div>
                        </div>

                        <!-- Payment method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metoda płatności</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <button type="button" @click="form.payment_method = 'card'" class="flex-1 py-3 text-center font-medium transition" :class="form.payment_method === 'card' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💳 Karta
                                </button>
                                <button type="button" @click="form.payment_method = 'cash'" class="flex-1 py-3 text-center font-medium transition" :class="form.payment_method === 'cash' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💵 Gotówka
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Opis (opcjonalnie)</label>
                            <input v-model="form.description" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                        </div>

                        <!-- Day of month -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dzień miesiąca</label>
                            <select v-model="form.day_of_month" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option v-for="d in 28" :key="d" :value="d">{{ d }}.</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Maksymalnie 28 — aby transakcja generowała się co miesiąc</p>
                            <div v-if="form.errors.day_of_month" class="text-red-500 text-sm mt-1">{{ form.errors.day_of_month }}</div>
                        </div>

                        <!-- Start date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data rozpoczęcia</label>
                            <input v-model="form.start_date" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <div v-if="form.errors.start_date" class="text-red-500 text-sm mt-1">{{ form.errors.start_date }}</div>
                        </div>

                        <!-- End date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data zakończenia (opcjonalnie)</label>
                            <input v-model="form.end_date" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <p class="text-xs text-gray-400 mt-1">Zostaw puste jeśli transakcja ma być bezterminowa (do odwołania)</p>
                            <div v-if="form.errors.end_date" class="text-red-500 text-sm mt-1">{{ form.errors.end_date }}</div>
                        </div>

                        <!-- Active toggle -->
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 dark:peer-focus:ring-indigo-600 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-indigo-600"></div>
                            </label>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ form.is_active ? 'Aktywna' : 'Wstrzymana' }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4">
                            <Link :href="route('recurring.index')" class="text-gray-500 dark:text-gray-400 hover:underline text-sm">Anuluj</Link>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2.5 px-6 rounded-lg transition">
                                Zapisz zmiany
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
