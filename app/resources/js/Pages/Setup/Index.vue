<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    categories: Array,
});

const step = ref(1);
const totalSteps = 3;

const form = useForm({
    card_balance: 0,
    cash_balance: 0,
    recurring: [],
});

// Recurring item form
const newItem = ref({
    category_id: '',
    amount: '',
    payment_method: 'card',
    description: '',
    day_of_month: 1,
});

function addRecurringItem() {
    if (!newItem.value.category_id || !newItem.value.amount) return;
    form.recurring.push({ ...newItem.value, amount: parseFloat(newItem.value.amount) });
    newItem.value = { category_id: '', amount: '', payment_method: 'card', description: '', day_of_month: 1 };
}

function removeRecurringItem(index) {
    form.recurring.splice(index, 1);
}

function getCategoryById(id) {
    return props.categories.find(c => c.id === id);
}

function nextStep() {
    if (step.value < totalSteps) step.value++;
}

function prevStep() {
    if (step.value > 1) step.value--;
}

function submit() {
    form.post(route('setup.store'));
}

function skip() {
    router.post(route('setup.skip'));
}

const progress = computed(() => (step.value / totalSteps) * 100);
</script>

<template>
    <Head title="Konfiguracja konta" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <!-- Header -->
            <div class="text-center mb-6">
                <ApplicationLogo class="w-16 h-16 mx-auto mb-3" />
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Witaj w Mój Budżet!</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Skonfiguruj swoje konto w kilku krokach</p>
            </div>

            <!-- Progress bar -->
            <div class="mb-6">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>Krok {{ step }} z {{ totalSteps }}</span>
                    <span>{{ Math.round(progress) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
                </div>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">

                <!-- Step 1: Balances -->
                <div v-if="step === 1">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">💰 Stan konta</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Podaj ile aktualnie masz na karcie i w gotówce</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                💳 Saldo na karcie (zł)
                            </label>
                            <input
                                v-model.number="form.card_balance"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-lg px-4 py-3"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                💵 Gotówka (zł)
                            </label>
                            <input
                                v-model.number="form.cash_balance"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-lg px-4 py-3"
                                placeholder="0.00"
                            />
                        </div>
                    </div>
                </div>

                <!-- Step 2: Recurring expenses -->
                <div v-if="step === 2">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">🔄 Stałe wydatki</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Dodaj cykliczne wydatki, np. czynsz, subskrypcje, raty</p>

                    <!-- Added items -->
                    <div v-if="form.recurring.length > 0" class="space-y-2 mb-4">
                        <div v-for="(item, idx) in form.recurring" :key="idx"
                            class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ getCategoryById(item.category_id)?.icon }}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ item.description || getCategoryById(item.category_id)?.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ item.amount.toFixed(2) }} zł · {{ item.day_of_month }}. dnia · {{ item.payment_method === 'card' ? '💳' : '💵' }}
                                    </div>
                                </div>
                            </div>
                            <button @click="removeRecurringItem(idx)" class="text-red-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add form -->
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kategoria</label>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button v-for="cat in categories" :key="cat.id" type="button"
                                    @click="newItem.category_id = cat.id"
                                    class="flex flex-col items-center p-2 rounded-lg border transition text-center"
                                    :class="newItem.category_id === cat.id
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                                    <span class="text-lg">{{ cat.icon }}</span>
                                    <span class="text-[10px] mt-0.5 text-gray-600 dark:text-gray-400 truncate w-full">{{ cat.name }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kwota (zł)</label>
                                <input v-model="newItem.amount" type="number" step="0.01" min="0.01"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"
                                    placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Dzień miesiąca</label>
                                <select v-model.number="newItem.day_of_month"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                                    <option v-for="d in 28" :key="d" :value="d">{{ d }}.</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Opis (opcjonalnie)</label>
                            <input v-model="newItem.description" type="text"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"
                                placeholder="Np. Czynsz, Netflix..." />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Płatność</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <button type="button" @click="newItem.payment_method = 'card'"
                                    class="flex-1 py-2 text-center text-sm font-medium transition"
                                    :class="newItem.payment_method === 'card' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💳 Karta
                                </button>
                                <button type="button" @click="newItem.payment_method = 'cash'"
                                    class="flex-1 py-2 text-center text-sm font-medium transition"
                                    :class="newItem.payment_method === 'cash' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    💵 Gotówka
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="addRecurringItem"
                            :disabled="!newItem.category_id || !newItem.amount"
                            class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 text-gray-700 dark:text-gray-200 font-medium py-2 rounded-lg transition text-sm">
                            + Dodaj wydatek
                        </button>
                    </div>

                    <p class="text-xs text-gray-400 mt-3 text-center">Ten krok jest opcjonalny — możesz dodać stałe wydatki później</p>
                </div>

                <!-- Step 3: Summary -->
                <div v-if="step === 3">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">✅ Podsumowanie</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Sprawdź czy wszystko się zgadza</p>

                    <div class="space-y-4">
                        <!-- Balances summary -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Stan konta</h3>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">💳 Karta</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ form.card_balance.toFixed(2) }} zł</span>
                            </div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">💵 Gotówka</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ form.cash_balance.toFixed(2) }} zł</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-600 mt-2 pt-2 flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Łącznie</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ (form.card_balance + form.cash_balance).toFixed(2) }} zł
                                </span>
                            </div>
                        </div>

                        <!-- Recurring summary -->
                        <div v-if="form.recurring.length > 0" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                Stałe wydatki ({{ form.recurring.length }})
                            </h3>
                            <div v-for="(item, idx) in form.recurring" :key="idx"
                                class="flex justify-between items-center text-sm py-1">
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ getCategoryById(item.category_id)?.icon }}
                                    {{ item.description || getCategoryById(item.category_id)?.name }}
                                    <span class="text-gray-400">({{ item.day_of_month }}.)</span>
                                </span>
                                <span class="font-medium text-red-600">-{{ item.amount.toFixed(2) }} zł</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-600 mt-2 pt-2 flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Razem miesięcznie</span>
                                <span class="font-bold text-red-600">
                                    -{{ form.recurring.reduce((s, i) => s + i.amount, 0).toFixed(2) }} zł
                                </span>
                            </div>
                        </div>

                        <p v-else class="text-sm text-gray-400 text-center py-2">Brak stałych wydatków</p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button v-if="step > 1" @click="prevStep" type="button"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-sm font-medium">
                        ← Wstecz
                    </button>
                    <button v-else @click="skip" type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-sm">
                        Pomiń konfigurację
                    </button>

                    <button v-if="step < totalSteps" @click="nextStep" type="button"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-lg transition text-sm">
                        Dalej →
                    </button>
                    <button v-else @click="submit" type="button"
                        :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2.5 px-6 rounded-lg transition text-sm">
                        Rozpocznij! 🚀
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
