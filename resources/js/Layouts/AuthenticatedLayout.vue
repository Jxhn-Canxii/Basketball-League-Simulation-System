<template>
    <div class="flex min-h-screen bg-gray-950 font-roboto">
        <!-- Sidebar -->
        <Navigation />

        <!-- Main Application Area -->
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <Header />

            <!-- Page Content -->
            <main
                class="min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden bg-gray-950"
            >
                <div
                    class="mx-auto w-full max-w-[2200px] min-w-0 px-2 py-3 sm:px-3 lg:px-4"
                >
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import Header from "@/Layouts/Header.vue";
import Navigation from "@/Layouts/Navigation.vue";
</script>