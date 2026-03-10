<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, Loader2, Lock, Mail } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <AuthBase>
        <Head title="Log in" />

        <div v-if="status" class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-center text-sm text-green-700">
            {{ status }}
        </div>

        <div class="mb-8">
            <h2 class="text-[22px] font-bold tracking-tight text-gray-900">Welcome back</h2>
            <p class="mt-1.5 text-sm text-gray-500">Sign in to your account to continue</p>
        </div>

        <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="flex flex-col gap-5">
            <!-- Email -->
            <div class="flex flex-col gap-1.5">
                <Label for="email" class="text-[13px] font-semibold text-gray-700">Email address</Label>
                <div class="relative">
                    <Mail class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="email" type="email" name="email" required autofocus :tabindex="1"
                        autocomplete="email" placeholder="name@company.co.id"
                        class="h-11 w-full rounded-[10px] border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <Label for="password" class="text-[13px] font-semibold text-gray-700">Password</Label>
                <div class="relative">
                    <Lock class="absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-gray-400" />
                    <input
                        id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                        :tabindex="2" autocomplete="current-password" placeholder="Enter your password"
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

            <!-- Remember me + Forgot password -->
            <div class="flex items-center justify-between">
                <Label class="flex cursor-pointer items-center gap-2">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-[13px] text-gray-600">Remember me</span>
                </Label>
                <Link v-if="canResetPassword" :href="request().url" :tabindex="5" class="text-[13px] font-medium text-blue-600 hover:text-blue-700">
                    Forgot password?
                </Link>
            </div>

            <!-- Submit -->
            <button type="submit" :tabindex="4" :disabled="processing"
                class="mt-1 flex h-11 w-full items-center justify-center gap-2 rounded-[10px] bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                <Loader2 v-if="processing" class="size-4 animate-spin" />
                <template v-else>
                    Sign in
                    <ArrowRight class="size-4" />
                </template>
            </button>
        </Form>

        <!-- Divider -->
        <div class="my-6 flex items-center gap-4">
            <div class="h-px flex-1 bg-gray-200" />
            <span class="text-[12px] font-medium text-gray-400">or continue with</span>
            <div class="h-px flex-1 bg-gray-200" />
        </div>

        <!-- Social buttons -->
        <div class="flex gap-3">
            <button type="button" class="flex h-11 flex-1 items-center justify-center gap-2.5 rounded-[10px] border border-gray-200 bg-white text-[13px] font-medium text-gray-700 transition hover:bg-gray-50">
                <svg width="17" height="17" viewBox="0 0 24 24" class="shrink-0">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 001 12c0 1.77.42 3.44 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Google
            </button>
            <button type="button" class="flex h-11 flex-1 items-center justify-center gap-2.5 rounded-[10px] border border-gray-200 bg-white text-[13px] font-medium text-gray-700 transition hover:bg-gray-50">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" class="shrink-0 text-gray-800">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.87 8.17 6.84 9.5.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.87 1.52 2.34 1.07 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.92 0-1.11.38-2 1.03-2.71-.1-.25-.45-1.29.1-2.64 0 0 .84-.27 2.75 1.02.79-.22 1.65-.33 2.5-.33.85 0 1.71.11 2.5.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.35.2 2.39.1 2.64.65.71 1.03 1.6 1.03 2.71 0 3.82-2.34 4.66-4.57 4.91.36.31.69.92.69 1.85V21.5c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0012 2z"/>
                </svg>
                GitHub
            </button>
        </div>

        <!-- Sign up link -->
        <p class="mt-8 text-center text-[13px] text-gray-500">
            Don't have an account?
            <Link v-if="canRegister" :href="register().url" class="font-semibold text-blue-600 hover:text-blue-700">Sign up</Link>
            <span v-else class="font-semibold text-blue-600">Contact IT Admin</span>
        </p>
    </AuthBase>
</template>
