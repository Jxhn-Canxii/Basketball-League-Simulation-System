<template>
    <div
        class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-black px-4 py-8 text-white sm:px-6"
    >
        <!-- Background -->
        <div class="pointer-events-none absolute inset-0">
            <!-- Subtle radial glows -->
            <div
                class="absolute left-1/2 top-0 h-[500px] w-[700px] -translate-x-1/2 rounded-full bg-blue-500/[0.04] blur-3xl"
            ></div>

            <div
                class="absolute bottom-0 left-0 h-[350px] w-[350px] rounded-full bg-yellow-500/[0.025] blur-3xl"
            ></div>

            <!-- Grid -->
            <div
                class="absolute inset-0 opacity-[0.025]"
                style="
                    background-image:
                        linear-gradient(rgba(255,255,255,.4) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255,255,255,.4) 1px, transparent 1px);
                    background-size: 40px 40px;
                "
            ></div>
        </div>

        <!-- Content -->
        <main class="relative z-10 flex w-full justify-center">
            <div class="w-full max-w-md">
                <!-- Brand -->
                <div class="mb-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-yellow-500/20 bg-yellow-500/[0.06] shadow-lg shadow-yellow-500/[0.03]"
                    >
                        <i class="fas fa-basketball-ball text-xl text-yellow-500"></i>
                    </div>

                    <div
                        class="text-[9px] font-black uppercase tracking-[0.35em] text-yellow-500/70"
                    >
                        LIGA2
                    </div>

                    <h1
                        class="mt-1 text-xl font-black tracking-tight text-white"
                    >
                        League Management
                    </h1>
                </div>

                <!-- Guest Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0b0b] shadow-2xl shadow-black/50"
                >
                    <!-- Top Accent -->
                    <div
                        class="h-0.5 w-full bg-gradient-to-r from-transparent via-yellow-500/70 to-transparent"
                    ></div>

                    <div class="p-5 sm:p-7">
                        <slot />
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-5 text-center">
                    <p
                        class="text-[8px] font-bold uppercase tracking-[0.22em] text-gray-700"
                    >
                        LIGA2
                        <span class="mx-1 text-gray-800">•</span>
                        League Simulation
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Dark scrollbar */
::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #292929;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #444;
}

/* Selection */
::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>