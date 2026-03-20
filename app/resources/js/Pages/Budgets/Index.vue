<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    budgets: Array,
    availableCategories: Array,
    currentMonth: String,
});

const showAddForm = ref(false);

const addForm = useForm({
    category_id: '',
    amount: '',
});

function submitAdd() {
    addForm.post(route('budgets.store'), {
        onSuccess: () => {
            addForm.reset();
            showAddForm.value = false;
        },
    });
}

function deleteBudget(id) {
    if (confirm('Usunąć ten limit?')) {
        router.delete(route('budgets.destroy', id));
    }
}

const editingId = ref(null);
const editAmount = ref('');

function startEdit(budget) {
    editingId.value = budget.id;
    editAmount.value = budget.limit;
}

function cancelEdit() {
    editingId.value = null;
    editAmount.value = '';
}

function saveEdit(id) {
    router.put(route('budgets.update', id), { amount: editAmount.value }, {
        onSuccess: () => cancelEdit(),
    });
}

function formatMoney(amount) {
    return Number(amount).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
}

function getBarColor(percentage) {
    if (percentage >= 100) return 'bg-red-500';
    if (percentage >= 80) return 'bg-orange-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
}

const totalLimit = computed(() => props.budgets.reduce((sum, b) => sum + b.limit, 0));
const totalSpent = computed(() => props.budgets.reduce((sum, b) => sum + b.spent, 0));
const totalPercentage = computed(() => totalLimit.value > 0 ? Math.round((totalSpent.value / totalLimit.value) * 100) : 0);
</script>

<template>
    <Head title="Budżety" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Budżety — {{ currentMonth }}
                </h2>
                <button v-if="availableCategories.length > 0" @click="showAddForm = !showAddForm" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                    + Dodaj
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Add budget form -->
                <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                    <div v-if="showAddForm" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">Dodaj limit budżetowy</h3>
                        <form @submit.prevent="submitAdd" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategoria</label>
                                <select v-model="addForm.category_id" class="w-full rounded-md border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                                    <option value="">Wybierz kategorię...</option>
                                    <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">{{ cat.icon }} {{ cat.name }}</option>
                                </select>
                                <div v-if="addForm.errors.category_id" class="text-red-500 text-xs mt-1">{{ addForm.errors.category_id }}</div>
                            </div>
                            <div class="flex-1 sm:max-w-xs">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Miesięczny limit (zł)</label>
                                <input v-model.number="addForm.amount" type="number" step="0.01" min="0.01" placeholder="0.00" class="w-full rounded-md border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" />
                                <div v-if="addForm.errors.amount" class="text-red-500 text-xs mt-1">{{ addForm.errors.amount }}</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="addForm.processing" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-5 rounded-lg transition text-sm whitespace-nowrap">
                                    Zapisz
                                </button>
                                <button type="button" @click="showAddForm = false; addForm.reset()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium py-2 px-4 rounded-lg transition text-sm">
                                    Anuluj
                                </button>
                            </div>
                        </form>
                    </div>
                </transition>

                <!-- Summary -->
                <div v-if="budgets.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200">Podsumowanie</h3>
                        <span class="text-sm font-medium" :class="totalPercentage >= 100 ? 'text-red-600' : totalPercentage >= 80 ? 'text-orange-600' : 'text-gray-700 dark:text-gray-300'">
                            {{ formatMoney(totalSpent) }} / {{ formatMoney(totalLimit) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div :class="getBarColor(totalPercentage)" class="h-3 rounded-full transition-all duration-500" :style="{ width: Math.min(totalPercentage, 100) + '%' }"></div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">{{ totalPercentage }}% wykorzystano</div>
                </div>

                <!-- Budget cards -->
                <div v-if="budgets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    <div v-for="b in budgets" :key="b.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ b.category_icon }}</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ b.category_name }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="b.percentage >= 100 ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : b.percentage >= 80 ? 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300' : 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'">
                                    {{ b.percentage }}%
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2">
                            <div :class="getBarColor(b.percentage)" class="h-2 rounded-full transition-all duration-500" :style="{ width: Math.min(b.percentage, 100) + '%' }"></div>
                        </div>
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="text-gray-500 dark:text-gray-400">Wydano: <span class="font-medium" :class="b.percentage >= 100 ? 'text-red-600' : b.percentage >= 80 ? 'text-orange-600' : 'text-gray-700 dark:text-gray-300'">{{ formatMoney(b.spent) }}</span></span>
                            <span class="text-gray-500 dark:text-gray-400">Limit: <span class="font-medium text-gray-700 dark:text-gray-300">{{ formatMoney(b.limit) }}</span></span>
                        </div>
                        <div v-if="b.percentage >= 100" class="text-xs text-red-600 dark:text-red-400 font-medium mb-2">
                            ⚠️ Przekroczono o {{ formatMoney(b.spent - b.limit) }}
                        </div>

                        <!-- Edit inline -->
                        <div v-if="editingId === b.id" class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <input v-model.number="editAmount" type="number" step="0.01" min="0.01" class="flex-1 rounded-md border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm" />
                            <button @click="saveEdit(b.id)" class="text-green-600 hover:text-green-700 text-sm font-medium">✓</button>
                            <button @click="cancelEdit" class="text-gray-400 hover:text-gray-600 text-sm font-medium">✕</button>
                        </div>
                        <div v-else class="flex items-center justify-end gap-3 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <button @click="startEdit(b)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edytuj</button>
                            <button @click="deleteBudget(b.id)" class="text-red-600 hover:underline text-xs">Usuń</button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="budgets.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center">
                    <div class="text-4xl mb-3">📊</div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">Brak limitów budżetowych</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Dodaj limity dla kategorii, aby kontrolować wydatki.</p>
                    <button @click="showAddForm = true" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg transition text-sm">
                        + Dodaj pierwszy budżet
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
