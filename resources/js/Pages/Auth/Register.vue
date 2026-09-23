<template>
    <Head title="Register" />

    <GuestLayout>
        <!-- Page Header -->
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
                Create Account
            </div>

            <h2
                class="mt-1 text-xl font-black tracking-tight text-white"
            >
                Join LIGA2
            </h2>

            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                Create your account to access the league management system.
            </p>
        </div>

        <!-- Registration Form -->
        <form
            @submit.prevent="submit"
            class="space-y-4"
        >
            <!-- Name -->
            <div>
                <InputLabel
                    for="name"
                    value="Name"
                    class="!text-[9px] !font-black !uppercase !tracking-[0.18em] !text-gray-500"
                />

                <div class="relative mt-1.5">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center"
                    >
                        <i
                            class="fas fa-user text-[10px] text-gray-700"
                        ></i>
                    </div>

                    <TextInput
                        id="name"
                        type="text"
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-3 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your name"
                    />
                </div>

                <InputError
                    class="mt-1.5"
                    :message="form.errors.name"
                />
            </div>

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
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-3 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Enter your email"
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
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center"
                    >
                        <i
                            class="fas fa-lock text-[10px] text-gray-700"
                        ></i>
                    </div>

                    <TextInput
                        id="password"
                        type="password"
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-3 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password"
                    />
                </div>

                <InputError
                    class="mt-1.5"
                    :message="form.errors.password"
                />
            </div>

            <!-- Confirm Password -->
            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="!text-[9px] !font-black !uppercase !tracking-[0.18em] !text-gray-500"
                />

                <div class="relative mt-1.5">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center"
                    >
                        <i
                            class="fas fa-shield-alt text-[10px] text-gray-700"
                        ></i>
                    </div>

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="!w-full !rounded-xl !border-white/[0.08] !bg-white/[0.03] !py-3 !pl-10 !pr-3 !text-sm !text-white !shadow-none placeholder:!text-gray-700 focus:!border-yellow-500/40 focus:!ring-1 focus:!ring-yellow-500/20"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                    />
                </div>

                <InputError
                    class="mt-1.5"
                    :message="form.errors.password_confirmation"
                />
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
                        class="fas fa-user-plus mr-2 text-[10px]"
                    ></i>

                    {{ form.processing ? "Creating Account..." : "Register" }}
                </PrimaryButton>
            </div>

            <!-- Login -->
            <div
                class="flex items-center justify-center gap-2 border-t border-white/[0.06] pt-5"
            >
                <span class="text-xs text-gray-700">
                    Already have an account?
                </span>

                <Link
                    :href="route('login')"
                    class="text-xs font-bold text-yellow-500 transition hover:text-yellow-400"
                >
                    Sign in
                    <i class="fas fa-arrow-right ml-1 text-[9px]"></i>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => {
            form.reset(
                "password",
                "password_confirmation"
            );
        },
    });
};
</script>

<style scoped>
::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>