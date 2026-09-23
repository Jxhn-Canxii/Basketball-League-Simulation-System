<template>
    <Head title="Awards" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 bg-[#080b10]"
                >
                    <i class="fas fa-trophy text-slate-300 text-sm"></i>
                </div>

                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white">
                        Awards
                    </h1>

                    <p class="text-[10px] uppercase tracking-widest text-slate-500">
                        League Honors & Player Recognition
                    </p>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-[#05070a] p-3 sm:p-4 lg:p-6">
            <div class="mx-auto w-full max-w-[1800px]">
                <!-- Page Header -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
                >
                    <!-- Accent -->
                    <div
                        class="h-1 bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
                    ></div>

                    <!-- Header -->
                    <div
                        class="border-b border-slate-800 bg-[#0d1117] px-5 py-5 sm:px-6"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-[#080b10]"
                                >
                                    <i
                                        class="fas fa-award text-slate-300 text-lg"
                                    ></i>
                                </div>

                                <div>
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.2em] text-slate-600"
                                    >
                                        League Recognition
                                    </p>

                                    <h2
                                        class="mt-1 text-xl font-black tracking-tight text-white sm:text-2xl"
                                    >
                                        Awards Center
                                    </h2>

                                    <p
                                        class="mt-1 text-xs text-slate-500 sm:text-sm"
                                    >
                                        Explore MVP history and individual
                                        player awards.
                                    </p>
                                </div>
                            </div>

                            <!-- Section Indicator -->
                            <div
                                class="flex items-center gap-2 self-start rounded-lg border border-slate-800 bg-[#080b10] px-3 py-2 lg:self-auto"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-slate-500"
                                ></span>

                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    {{ activeTabLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div
                        class="border-b border-slate-800 bg-[#0a0d12] px-4 pt-3 sm:px-5"
                    >
                        <nav
                            class="flex w-full gap-1 overflow-x-auto"
                            aria-label="Awards navigation"
                        >
                            <!-- MVP Tab -->
                            <button
                                type="button"
                                @click="activeTab = 'mvpList'"
                                :class="[
                                    'group relative flex min-w-[150px] items-center justify-center gap-2 rounded-t-xl px-4 py-3 text-xs font-bold transition-all duration-200 sm:min-w-[180px]',
                                    activeTab === 'mvpList'
                                        ? 'bg-[#0d1117] text-white'
                                        : 'text-slate-600 hover:bg-[#0d1117]/60 hover:text-slate-300',
                                ]"
                            >
                                <i
                                    :class="[
                                        'fas fa-trophy text-xs transition-colors',
                                        activeTab === 'mvpList'
                                            ? 'text-slate-300'
                                            : 'text-slate-700 group-hover:text-slate-500',
                                    ]"
                                ></i>

                                <span>MVP History</span>

                                <span
                                    v-if="activeTab === 'mvpList'"
                                    class="absolute bottom-0 left-4 right-4 h-0.5 bg-slate-400"
                                ></span>
                            </button>

                            <!-- Player Awards Tab -->
                            <button
                                type="button"
                                @click="activeTab = 'playerAwards'"
                                :class="[
                                    'group relative flex min-w-[150px] items-center justify-center gap-2 rounded-t-xl px-4 py-3 text-xs font-bold transition-all duration-200 sm:min-w-[180px]',
                                    activeTab === 'playerAwards'
                                        ? 'bg-[#0d1117] text-white'
                                        : 'text-slate-600 hover:bg-[#0d1117]/60 hover:text-slate-300',
                                ]"
                            >
                                <i
                                    :class="[
                                        'fas fa-medal text-xs transition-colors',
                                        activeTab === 'playerAwards'
                                            ? 'text-slate-300'
                                            : 'text-slate-700 group-hover:text-slate-500',
                                    ]"
                                ></i>

                                <span>Player Awards</span>

                                <span
                                    v-if="activeTab === 'playerAwards'"
                                    class="absolute bottom-0 left-4 right-4 h-0.5 bg-slate-400"
                                ></span>
                            </button>
                        </nav>
                    </div>

                    <!-- Content -->
                    <div class="bg-[#080b10] p-3 sm:p-4 lg:p-5">
                        <Transition
                            name="tab"
                            mode="out-in"
                        >
                            <div
                                v-if="activeTab === 'mvpList'"
                                key="mvpList"
                            >
                                <MVPList />
                            </div>

                            <div
                                v-else
                                key="playerAwards"
                            >
                                <PlayerAwards />
                            </div>
                        </Transition>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { Head } from "@inertiajs/vue3";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PlayerAwards from "./Module/PlayerAwards.vue";
import MVPList from "./Module/MVPList.vue";

const activeTab = ref("mvpList");

const activeTabLabel = computed(() => {
    return activeTab.value === "mvpList"
        ? "MVP History"
        : "Player Awards";
});
</script>

<style scoped>
.tab-enter-active,
.tab-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.tab-enter-from {
    opacity: 0;
    transform: translateY(4px);
}

.tab-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>