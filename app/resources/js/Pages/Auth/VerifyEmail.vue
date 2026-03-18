<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Weryfikacja email" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Dziękujemy za rejestrację! Zanim zaczniesz, zweryfikuj swój
            adres email klikając w link, który właśnie do Ciebie wysłaliśmy.
            Jeśli nie otrzymałeś wiadomości, chętnie wyślemy kolejną.
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Nowy link weryfikacyjny został wysłany na adres email
            podany podczas rejestracji.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Wyślij ponownie email weryfikacyjny
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    >Wyloguj się</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
