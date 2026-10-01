<template>
    <div class="min-w-0 w-full">
        <Head title="System" />

        <AuthenticatedLayout>
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="fa fa-medical text-rose-400"></i>
                    <span>Health Check</span>
                </div>
            </template>

        <div class="w-full min-w-0 rounded-lg bg-black text-gray-200">

            <!-- =========================================================
                HEADER
            ========================================================== -->
            <div
                class="flex flex-col gap-3 border-b border-gray-800 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-800 bg-gray-900"
                    >
                        <i class="fas fa-heartbeat text-sm text-red-400"></i>
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-white">
                            System Health
                        </h2>

                        <p class="truncate text-[10px] text-gray-500">
                            LIGA2 system monitoring
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">

                    <!-- Auto refresh -->
                    <button
                        type="button"
                        @click="toggleAutoRefresh"
                        class="flex items-center gap-1.5 rounded-md border border-gray-800 bg-gray-900 px-2 py-1.5 text-[10px] transition hover:border-gray-700 hover:bg-gray-800"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="
                                autoRefresh
                                    ? 'animate-pulse bg-green-400'
                                    : 'bg-gray-600'
                            "
                        ></span>

                        <span
                            :class="
                                autoRefresh
                                    ? 'text-green-400'
                                    : 'text-gray-500'
                            "
                        >
                            {{ autoRefresh ? 'AUTO' : 'MANUAL' }}
                        </span>
                    </button>

                    <!-- Refresh -->
                    <button
                        type="button"
                        @click="loadHealth"
                        :disabled="loading"
                        class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-800 bg-gray-900 text-gray-400 transition hover:border-gray-700 hover:bg-gray-800 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        title="Refresh health check"
                    >
                        <i
                            class="fas fa-sync-alt text-[10px]"
                            :class="{ 'animate-spin': loading }"
                        ></i>
                    </button>
                </div>
            </div>

            <!-- =========================================================
                LOADING
            ========================================================== -->
            <div
                v-if="loading && !health"
                class="flex min-h-[220px] items-center justify-center"
            >
                <div class="text-center">
                    <i
                        class="fas fa-circle-notch animate-spin text-lg text-gray-600"
                    ></i>

                    <p class="mt-2 text-[10px] text-gray-600">
                        Checking system...
                    </p>
                </div>
            </div>

            <!-- =========================================================
                ERROR
            ========================================================== -->
            <div
                v-else-if="error && !health"
                class="m-3 rounded-md border border-red-900/50 bg-red-950/20 p-3"
            >
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-circle mt-0.5 text-xs text-red-400"></i>

                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-red-300">
                            Health check unavailable
                        </div>

                        <div class="mt-1 break-words text-[10px] text-red-400/70">
                            {{ error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- =========================================================
                HEALTH CONTENT
            ========================================================== -->
            <div v-else-if="health" class="p-3">

                <!-- =====================================================
                    OVERALL STATUS
                ====================================================== -->
                <div
                    class="mb-3 flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between"
                    :class="overallContainerClass"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            :class="overallIconContainerClass"
                        >
                            <i
                                class="fas text-sm"
                                :class="overallIconClass"
                            ></i>
                        </div>

                        <div class="min-w-0">
                            <div class="text-[9px] uppercase tracking-widest text-gray-500">
                                Overall Status
                            </div>

                            <div
                                class="mt-0.5 text-sm font-black uppercase tracking-wide"
                                :class="statusTextClass(health.status)"
                            >
                                {{ health.status }}
                            </div>

                            <div class="mt-0.5 text-[10px] text-gray-500">
                                Last checked {{ formattedTime }}
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div
                        class="grid grid-cols-3 divide-x divide-gray-800 overflow-hidden rounded-md border border-gray-800 bg-black/40"
                    >
                        <div class="min-w-[55px] px-3 py-2 text-center">
                            <div class="text-[8px] uppercase text-gray-600">
                                Healthy
                            </div>

                            <div class="mt-0.5 text-sm font-bold text-green-400">
                                {{ health.summary?.healthy ?? 0 }}
                            </div>
                        </div>

                        <div class="min-w-[55px] px-3 py-2 text-center">
                            <div class="text-[8px] uppercase text-gray-600">
                                Warning
                            </div>

                            <div class="mt-0.5 text-sm font-bold text-yellow-400">
                                {{ health.summary?.warning ?? 0 }}
                            </div>
                        </div>

                        <div class="min-w-[55px] px-3 py-2 text-center">
                            <div class="text-[8px] uppercase text-gray-600">
                                Failed
                            </div>

                            <div class="mt-0.5 text-sm font-bold text-red-400">
                                {{ health.summary?.failed ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                    APPLICATION INFO
                ====================================================== -->
                <div
                    v-if="health.application"
                    class="mb-3 overflow-hidden rounded-lg border border-gray-800 bg-gray-950"
                >
                    <div
                        class="flex items-center gap-2 border-b border-gray-800 px-3 py-2"
                    >
                        <i class="fas fa-server text-[10px] text-gray-600"></i>

                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-500">
                            Application
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-px bg-gray-800 sm:grid-cols-4"
                    >
                        <InfoCell
                            label="Application"
                            :value="health.application.name"
                        />

                        <InfoCell
                            label="Environment"
                            :value="health.application.environment"
                        />

                        <InfoCell
                            label="Laravel"
                            :value="health.application.laravel"
                        />

                        <InfoCell
                            label="PHP"
                            :value="health.application.php"
                        />
                    </div>
                </div>

                <!-- =====================================================
                    CHECKS
                ====================================================== -->
                <div
                    class="overflow-hidden rounded-lg border border-gray-800 bg-gray-950"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-800 px-3 py-2"
                    >
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-alt text-[10px] text-gray-600"></i>

                            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-500">
                                Health Checks
                            </span>
                        </div>

                        <span class="text-[9px] text-gray-700">
                            READ ONLY
                        </span>
                    </div>

                    <div class="divide-y divide-gray-900">

                        <div
                            v-for="(check, name) in health.checks"
                            :key="name"
                            class="group px-3 py-2.5 transition hover:bg-gray-900/40"
                        >
                            <div
                                class="flex min-w-0 items-center justify-between gap-3"
                            >
                                <!-- Left -->
                                <div class="flex min-w-0 items-center gap-2.5">

                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border"
                                        :class="statusIconContainer(check.status)"
                                    >
                                        <i
                                            class="fas text-[9px]"
                                            :class="statusIcon(check.status)"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-xs font-semibold text-gray-300"
                                        >
                                            {{ formatName(name) }}
                                        </div>

                                        <div
                                            class="truncate text-[9px] text-gray-600"
                                            :title="check.message"
                                        >
                                            {{ check.message }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <span
                                    class="shrink-0 rounded-full border px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider"
                                    :class="statusBadge(check.status)"
                                >
                                    {{ check.status }}
                                </span>
                            </div>

                            <!-- Details -->
                            <div
                                v-if="hasDetails(check)"
                                class="mt-2 ml-9 grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-5"
                            >
                                <div
                                    v-for="(value, key) in check.details"
                                    :key="key"
                                    class="min-w-0 rounded border border-gray-900 bg-black px-2 py-1.5"
                                >
                                    <div
                                        class="truncate text-[7px] uppercase tracking-wider text-gray-700"
                                    >
                                        {{ formatName(key) }}
                                    </div>

                                    <div
                                        class="mt-0.5 truncate text-[9px] font-medium text-gray-500"
                                        :title="formatValue(value)"
                                    >
                                        {{ formatValue(value) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Error -->
                            <div
                                v-if="check.error"
                                class="mt-2 ml-9 rounded border border-red-900/30 bg-red-950/10 px-2 py-1.5"
                            >
                                <div class="break-all text-[9px] text-red-400/70">
                                    {{ check.error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                    FOOTER
                ====================================================== -->
                <div
                    class="mt-2 flex flex-col gap-1 px-1 text-[8px] text-gray-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>
                        {{ health.summary?.total ?? 0 }} checks monitored
                    </span>

                    <span>
                        Auto refresh: {{ autoRefresh ? '30s' : 'off' }}
                    </span>
                </div>
            </div>
        </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import {
    computed,
    h,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue'

/*
|--------------------------------------------------------------------------
| Small inline component
|--------------------------------------------------------------------------
*/

const InfoCell = {
    props: {
        label: String,
        value: [String, Number, Boolean],
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class: 'min-w-0 bg-gray-950 px-3 py-2.5',
                },
                [
                    h(
                        'div',
                        {
                            class: 'truncate text-[8px] uppercase tracking-wider text-gray-700',
                        },
                        props.label
                    ),

                    h(
                        'div',
                        {
                            class: 'mt-0.5 truncate text-[10px] font-semibold text-gray-400',
                        },
                        props.value ?? '-'
                    ),
                ]
            )
    },
}

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const health = ref(null)
const loading = ref(false)
const error = ref(null)
const autoRefresh = ref(true)

let refreshTimer = null

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

const loadHealth = async () => {
    if (loading.value) {
        return
    }

    loading.value = true
    error.value = null

    try {
        /*
         * Your API route is named:
         *
         * system.health
         *
         * If Ziggy is installed, route() resolves it.
         */
        const url =
            typeof route === 'function'
                ? route('system.health')
                : '/system/health'

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        const data = await response.json()

        /*
         * HTTP 503 is expected when the service
         * reports a failed health check.
         *
         * We still want to display the response.
         */
        health.value = data

        if (!response.ok && !data) {
            throw new Error(
                `Health endpoint returned HTTP ${response.status}`
            )
        }
    } catch (err) {
        error.value =
            err?.message ||
            'Unable to connect to the health check endpoint.'
    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Auto Refresh
|--------------------------------------------------------------------------
*/

const startAutoRefresh = () => {
    stopAutoRefresh()

    if (!autoRefresh.value) {
        return
    }

    refreshTimer = setInterval(() => {
        loadHealth()
    }, 30000)
}

const stopAutoRefresh = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer)
        refreshTimer = null
    }
}

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value
}

watch(autoRefresh, () => {
    startAutoRefresh()
})

/*
|--------------------------------------------------------------------------
| Overall Styling
|--------------------------------------------------------------------------
*/

const overallContainerClass = computed(() => {
    if (!health.value) {
        return 'border-gray-800 bg-gray-950'
    }

    switch (health.value.status) {
        case 'healthy':
            return 'border-green-900/40 bg-green-950/10'

        case 'warning':
            return 'border-yellow-900/40 bg-yellow-950/10'

        default:
            return 'border-red-900/40 bg-red-950/10'
    }
})

const overallIconContainerClass = computed(() => {
    if (!health.value) {
        return 'bg-gray-900'
    }

    switch (health.value.status) {
        case 'healthy':
            return 'bg-green-950/40'

        case 'warning':
            return 'bg-yellow-950/40'

        default:
            return 'bg-red-950/40'
    }
})

const overallIconClass = computed(() => {
    if (!health.value) {
        return 'text-gray-500 fa-question'
    }

    switch (health.value.status) {
        case 'healthy':
            return 'text-green-400 fa-check'

        case 'warning':
            return 'text-yellow-400 fa-exclamation'

        default:
            return 'text-red-400 fa-times'
    }
})

/*
|--------------------------------------------------------------------------
| Status Styling
|--------------------------------------------------------------------------
*/

const statusTextClass = (status) => {
    switch (status) {
        case 'healthy':
            return 'text-green-400'

        case 'warning':
            return 'text-yellow-400'

        default:
            return 'text-red-400'
    }
}

const statusIconContainer = (status) => {
    switch (status) {
        case 'healthy':
            return 'border-green-900/40 bg-green-950/20 text-green-400'

        case 'warning':
            return 'border-yellow-900/40 bg-yellow-950/20 text-yellow-400'

        default:
            return 'border-red-900/40 bg-red-950/20 text-red-400'
    }
}

const statusIcon = (status) => {
    switch (status) {
        case 'healthy':
            return 'fa-check'

        case 'warning':
            return 'fa-exclamation'

        default:
            return 'fa-times'
    }
}

const statusBadge = (status) => {
    switch (status) {
        case 'healthy':
            return 'border-green-900/40 bg-green-950/20 text-green-400'

        case 'warning':
            return 'border-yellow-900/40 bg-yellow-950/20 text-yellow-400'

        default:
            return 'border-red-900/40 bg-red-950/20 text-red-400'
    }
}

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatName = (value) => {
    if (!value) {
        return '-'
    }

    return String(value)
        .replace(/_/g, ' ')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const formatValue = (value) => {
    if (value === null || value === undefined) {
        return '-'
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No'
    }

    if (typeof value === 'object') {
        return JSON.stringify(value)
    }

    return String(value)
}

const hasDetails = (check) => {
    return (
        check &&
        check.details &&
        typeof check.details === 'object' &&
        Object.keys(check.details).length > 0
    )
}

const formattedTime = computed(() => {
    if (!health.value?.timestamp) {
        return '-'
    }

    try {
        return new Date(
            health.value.timestamp
        ).toLocaleTimeString()
    } catch {
        return '-'
    }
})

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    loadHealth()
    startAutoRefresh()
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>
