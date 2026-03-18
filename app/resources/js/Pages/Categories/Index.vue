<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
});

const showForm = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name: '',
    type: 'expense',
    icon: '📦',
    color: '#6366f1',
});

const icons = ['🛒', '🍔', '🏠', '🚗', '💊', '🎮', '👕', '📱', '✈️', '📚', '💡', '🎬', '🏋️', '🎁', '💰', '💼', '📦', '🎵', '🐶', '🌿', '💳', '🏦', '📈', '💸'];

const colors = ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'];

function startCreate() {
    editingCategory.value = null;
    form.reset();
    form.type = 'expense';
    form.icon = '📦';
    form.color = '#6366f1';
    showForm.value = true;
}

function startEdit(category) {
    editingCategory.value = category;
    form.name = category.name;
    form.type = category.type;
    form.icon = category.icon;
    form.color = category.color;
    showForm.value = true;
}

function submit() {
    if (editingCategory.value) {
        form.put(route('categories.update', editingCategory.value.id), {
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        form.post(route('categories.store'), {
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
}

function deleteCategory(id) {
    if (confirm('Czy na pewno chcesz usunąć tę kategorię?')) {
        router.delete(route('categories.destroy', id));
    }
}
</script>

<template>
    <Head title="Kategorie" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Kategorie</h2>
                <button @click="startCreate" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                    + Dodaj
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Add/Edit Form -->
                <div v-if="showForm" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        {{ editingCategory ? 'Edytuj kategorię' : 'Nowa kategoria' }}
                    </h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nazwa</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="Nazwa kategorii" />
                            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Typ</label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <button type="button" @click="form.type = 'expense'" class="flex-1 py-2 text-center font-medium transition" :class="form.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    Wydatek
                                </button>
                                <button type="button" @click="form.type = 'income'" class="flex-1 py-2 text-center font-medium transition" :class="form.type === 'income' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
                                    Przychód
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ikona</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="icon in icons" :key="icon" type="button" @click="form.icon = icon" class="w-10 h-10 flex items-center justify-center rounded-lg border-2 text-lg transition" :class="form.icon === icon ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-600'">
                                    {{ icon }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kolor</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="color in colors" :key="color" type="button" @click="form.color = color" class="w-8 h-8 rounded-full border-2 transition" :class="form.color === color ? 'border-gray-800 dark:border-white scale-110' : 'border-transparent'" :style="{ backgroundColor: color }" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="showForm = false" class="text-gray-500 dark:text-gray-400 hover:underline text-sm">Anuluj</button>
                            <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-6 rounded-lg transition">
                                {{ editingCategory ? 'Zapisz zmiany' : 'Dodaj kategorię' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Expense categories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Kategorie wydatków</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                        <div v-for="cat in categories.filter(c => c.type === 'expense')" :key="cat.id" class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl border border-gray-100 dark:border-gray-700" :style="{ borderLeftWidth: '4px', borderLeftColor: cat.color }">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ cat.icon }}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ cat.name }}</div>
                                    <div class="text-xs text-gray-500">{{ cat.transactions_count }} transakcji</div>
                                </div>
                            </div>
                            <div v-if="!cat.is_default" class="flex gap-2">
                                <button @click="startEdit(cat)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edytuj</button>
                                <button @click="deleteCategory(cat.id)" class="text-red-600 hover:underline text-xs">Usuń</button>
                            </div>
                            <span v-else class="text-xs text-gray-400">Domyślna</span>
                        </div>
                    </div>
                </div>

                <!-- Income categories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-5">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-4">Kategorie przychodów</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                        <div v-for="cat in categories.filter(c => c.type === 'income')" :key="cat.id" class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl border border-gray-100 dark:border-gray-700" :style="{ borderLeftWidth: '4px', borderLeftColor: cat.color }">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ cat.icon }}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ cat.name }}</div>
                                    <div class="text-xs text-gray-500">{{ cat.transactions_count }} transakcji</div>
                                </div>
                            </div>
                            <div v-if="!cat.is_default" class="flex gap-2">
                                <button @click="startEdit(cat)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edytuj</button>
                                <button @click="deleteCategory(cat.id)" class="text-red-600 hover:underline text-xs">Usuń</button>
                            </div>
                            <span v-else class="text-xs text-gray-400">Domyślna</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
