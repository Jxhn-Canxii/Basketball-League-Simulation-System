<template>
    <Head title="Log in" />

    <GuestLayout>
        <!-- Header -->
        <div class="mb-6 text-center">
            <!-- <Link
                href="/"
                class="group mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/[0.08] bg-white/[0.03] transition hover:border-yellow-500/30 hover:bg-yellow-500/[0.05]"
            >
                <ApplicationLogo
                    class="h-9 w-9 fill-current text-gray-500 transition group-hover:text-yellow-500"
                />
            </Link> -->

            <div
                class="text-[9px] font-black uppercase tracking-[0.3em] text-yellow-500/70"
            >
                Welcome Back
            </div>

            <h2
                class="mt-1 text-xl font-black tracking-tight text-white"
            >
                Sign In
            </h2>

            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                Sign in to continue to your LIGA2 league management account.
            </p>
        </div>

        <!-- Status -->
        <div
            v-if="status"
            class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-500/15 bg-emerald-500/[0.06] px-3.5 py-3"
        >
            <div
                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-500/[0.1]"
            >
                <i class="fas fa-check text-[9px] text-emerald-400"></i>
            </div>

            <p class="text-xs leading-relaxed text-emerald-400">
                {{ status }}
            </p>
        </div>

        <!-- Login Form -->
        <form
            @submit.prevent="login"
            class="space-y-4"
        >
            <!-- Email -->
            <div>
                <InputLabel
                    for="email"
                    value="Email"
                    class="!text-[9px] !font-black !uppercase !tracking-[0.18em] !text-gray-500"
                />

                <div class="relative mt-1.5">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center"
                    >
                        <i
                            class="fas fa-envelope text-[10px] text-gray-700"
                        ></i>
                    </div>

                    <TextInput
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Enter your email"
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-3 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                    />
                </div>

                <InputError
                    class="mt-1.5"
                    :message="form.errors.email"
                />
            </div>

            <!-- Password -->
            <div>
                <InputLabel
                    for="password"
                    value="Password"
                    class="!text-[9px] !font-black !uppercase !tracking-[0.18em] !text-gray-500"
                />

                <div class="relative mt-1.5">
                    <!-- Lock Icon -->
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 z-10 flex w-10 items-center justify-center"
                    >
                        <i
                            class="fas fa-lock text-[10px] text-gray-700"
                        ></i>
                    </div>

                    <TextInput
                        id="password"
                        :type="form.show ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-12 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                    />

                    <!-- Password Toggle -->
                    <button
                        type="button"
                        @click.prevent="togglePasswordVisibility"
                        :aria-label="
                            form.show
                                ? 'Hide password'
                                : 'Show password'
                        "
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-gray-600 transition hover:text-yellow-500"
                    >
                        <i
                            :class="
                                form.show
                                    ? 'fa fa-eye'
                                    : 'fa fa-eye-slash'
                            "
                            class="text-[11px]"
                        ></i>
                    </button>
                </div>

                <InputError
                    class="mt-1.5"
                    :message="form.errors.password"
                />
            </div>

            <!-- Remember / Forgot -->
            <div
                class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between"
            >
                <label
                    for="remember"
                    class="group inline-flex cursor-pointer items-center"
                >
                    <Checkbox
                        id="remember"
                        name="remember"
                        v-model:checked="form.remember"
                        class="!border-white/[0.12] !bg-white/[0.03] checked:!border-yellow-500 checked:!bg-yellow-500 focus:!ring-yellow-500/20"
                    />

                    <span
                        class="ml-2 text-xs text-gray-600 transition group-hover:text-gray-400"
                    >
                        Remember me
                    </span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-bold text-gray-600 underline decoration-white/[0.08] underline-offset-4 transition hover:text-yellow-500"
                >
                    Forgot your password?
                </Link>
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <PrimaryButton
                    type="submit"
                    :disabled="form.processing"
                    :class="[
                        '!flex !w-full !items-center !justify-center !rounded-xl !border !border-yellow-500/20 !bg-yellow-500 !px-4 !py-3 !text-xs !font-black !uppercase !tracking-wider !text-black !shadow-lg !shadow-yellow-500/[0.08] transition-all',
                        form.processing
                            ? '!cursor-not-allowed !opacity-40'
                            : 'hover:!bg-yellow-400 hover:!shadow-yellow-500/[0.15]',
                    ]"
                >
                    <i
                        v-if="form.processing"
                        class="fas fa-circle-notch mr-2 animate-spin text-[10px]"
                    ></i>

                    <i
                        v-else
                        class="fas fa-sign-in-alt mr-2 text-[10px]"
                    ></i>

                    {{ form.processing ? "Signing In..." : "Log In" }}
                </PrimaryButton>
            </div>
        </form>

        <!-- Register -->
        <div
            class="mt-5 flex items-center justify-center gap-2 border-t border-white/[0.06] pt-5"
        >
            <span class="text-xs text-gray-700">
                Don't have an account?
            </span>

            <Link
                :href="route('register')"
                class="text-xs font-bold text-yellow-500 transition hover:text-yellow-400"
            >
                Create account
                <i class="fas fa-arrow-right ml-1 text-[9px]"></i>
            </Link>
        </div>
    </GuestLayout>
</template>

<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    show: false,
    remember: false,
});

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/
const login = () => {
    form.post(route("login"), {
        onFinish: () => {
            form.reset("password");
        },
    });
};

/*
|--------------------------------------------------------------------------
| Password Visibility
|--------------------------------------------------------------------------
*/
const togglePasswordVisibility = () => {
    form.show = !form.show;
};
</script>

<style scoped>
::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>