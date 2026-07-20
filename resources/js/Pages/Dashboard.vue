<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Trippage from '@/Pages/Trippage.vue';
</script>

<template>
    <Head title="Trip Planner" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Trip Planner</h2>
                <p class="mt-1 text-sm text-slate-500">Build, reorder and visualise a saved itinerary.</p>
            </div>
        </template>

        <main class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Trippage />
            </div>
        </main>
    </AuthenticatedLayout>
</template>
