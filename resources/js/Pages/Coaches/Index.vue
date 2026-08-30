<template>
    <Head title="Free Agents" />

    <AuthenticatedLayout>
        <template #header>
           Coaches
        </template>

        <FreeAgentsCoach :showControls="false"  />

    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3';
import FreeAgentsCoach from './FreeAgentsCoach.vue';
</script>
