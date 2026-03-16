<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail, User } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <AuthBase>
        <Head title="Register" />

        <div class="mb-8">
            <h2 class="text-[22px] font-bold tracking-tight text-gray-900">Create an account</h2>
            <p class="mt-1.5 text-sm text-gray-500">Enter your details below to create your account</p>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-[13px] font-semibold text-gray-700">Full Name</label>
                <div class="relative">
                    <User class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="name" type="text" name="name" required autofocus :tabindex="1"
                        autocomplete="name" placeholder="Full name"
                        class="h-11 w-full rounded-[10px] border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-[13px] font-semibold text-gray-700">Email address</label>
                <div class="relative">
                    <Mail class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="email" type="email" name="email" required :tabindex="2"
                        autocomplete="email" placeholder="email@example.com"
                        class="h-11 w-full rounded-[10px] border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-[13px] font-semibold text-gray-700">Password</label>
                <div class="relative">
                    <Lock class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                        :tabindex="3" autocomplete="new-password" placeholder="Password"
                        class="h-11 w-full rounded-[10px] border border-gray-200 bg-white pl-10 pr-11 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-gray-400 transition-colors hover:text-gray-600"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                        <EyeOff v-if="showPassword" class="size-[17px]" />
                        <Eye v-else class="size-[17px]" />
                    </button>
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-[13px] font-semibold text-gray-700">Confirm password</label>
                <div class="relative">
                    <Lock class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation" required
                        :tabindex="4" autocomplete="new-password" placeholder="Confirm password"
                        class="h-11 w-full rounded-[10px] border border-gray-200 bg-white pl-10 pr-11 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-gray-400 transition-colors hover:text-gray-600"
                        :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'">
                        <EyeOff v-if="showConfirmPassword" class="size-[17px]" />
                        <Eye v-else class="size-[17px]" />
                    </button>
                </div>
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Submit -->
            <button type="submit" :tabindex="5" :disabled="processing"
                data-test="register-user-button"
                class="mt-1 flex h-11 w-full items-center justify-center gap-2 rounded-[10px] bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                <svg v-if="processing" class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <template v-else>Create account</template>
            </button>
        </Form>

        <!-- Log in link -->
        <p class="mt-8 text-center text-[13px] text-gray-500">
            Already have an account?
            <Link :href="login()" :tabindex="6" class="font-semibold text-blue-600 hover:text-blue-700">Log in</Link>
        </p>
    </AuthBase>
</template>
