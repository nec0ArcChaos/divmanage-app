<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Bell, Eye, EyeOff, KeyRound, Lock, Phone, Save, Shield, User, UserCog, Users } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { getInitials } from '@/composables/useInitials';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { update as updateProfile } from '@/routes/profile';
import { update as updatePassword } from '@/routes/user-password';

defineOptions({ layout: DashboardLayout });

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
const userInitials = computed(() => getInitials(user.value?.name));

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const navItems = [
    { key: 'profile',       label: 'Profile',       icon: User,    href: '/settings/profile', active: true  },
    { key: 'workspace',     label: 'Workspace',     icon: UserCog, href: '#',                 active: false },
    { key: 'users',         label: 'Users & Roles', icon: Users,   href: '#',                 active: false },
    { key: 'notifications', label: 'Notifications', icon: Bell,    href: '#',                 active: false },
    { key: 'security',      label: 'Security',      icon: Shield,  href: '#',                 active: false },
];
</script>

<template>
    <Head title="Profile Settings" />

    <!-- Page header -->
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-gray-900">Settings</h1>
        <p class="mt-0.5 text-[13px] text-gray-500">Manage your account and preferences</p>
    </div>

    <div class="flex gap-8">
        <!-- Sidebar nav -->
        <nav class="w-[200px] shrink-0">
            <div class="flex flex-col gap-0.5">
                <a
                    v-for="item in navItems"
                    :key="item.key"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-all',
                        item.active
                            ? 'bg-blue-50 text-blue-700'
                            : 'cursor-not-allowed select-none text-gray-400',
                    ]"
                    @click.prevent="item.active ? undefined : $event.preventDefault()"
                >
                    <component :is="item.icon" class="size-[15px] shrink-0" />
                    {{ item.label }}
                </a>
            </div>
        </nav>

        <!-- Content -->
        <div class="min-w-0 flex-1">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <!-- Card header -->
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-[15px] font-semibold text-gray-900">Profile Information</h2>
                    <p class="mt-0.5 text-[13px] text-gray-400">Update your personal details and contact info</p>
                </div>

                <div class="px-6 py-6">

                    <!-- Avatar row -->
                    <div class="mb-6 flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-[22px] font-bold text-white">
                            {{ userInitials }}
                        </div>
                        <div>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[12.5px] font-medium text-gray-600 transition hover:bg-gray-50 hover:border-gray-300"
                            >
                                Upload Photo
                            </button>
                            <p class="mt-1 text-[11.5px] text-gray-400">JPG, PNG, max 2MB</p>
                        </div>
                    </div>

                    <!-- Profile form -->
                    <Form
                        v-bind="updateProfile.form()"
                        v-slot="{ errors, processing, recentlySuccessful }"
                        class="space-y-4"
                    >
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Full Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    :default-value="user?.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Your full name"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <!-- Email -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Email Address</label>
                                <input
                                    type="email"
                                    name="email"
                                    :default-value="user?.email"
                                    required
                                    autocomplete="email"
                                    placeholder="name@company.co.id"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                />
                                <InputError :message="errors.email" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Username -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Username</label>
                                <input
                                    type="text"
                                    name="username"
                                    :default-value="user?.username"
                                    required
                                    autocomplete="username"
                                    placeholder="username"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                />
                                <InputError :message="errors.username" />
                            </div>
                            <!-- Phone -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Phone Number</label>
                                <div class="relative">
                                    <Phone class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="tel"
                                        name="phone"
                                        :default-value="user?.phone"
                                        autocomplete="tel"
                                        placeholder="+62 812-xxxx-xxxx"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                </div>
                                <InputError :message="errors.phone" />
                            </div>
                        </div>

                        <!-- Unverified email notice -->
                        <p v-if="mustVerifyEmail && !user?.email_verified_at" class="text-[12.5px] text-amber-600">
                            Your email address is unverified.
                        </p>

                        <div class="flex items-center gap-3 pt-1">
                            <button
                                type="submit"
                                :disabled="processing"
                                class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Save class="size-3.5" />
                                Save Changes
                            </button>
                            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                                <span v-if="recentlySuccessful" class="text-[12.5px] font-medium text-green-600">Saved successfully!</span>
                            </Transition>
                        </div>
                    </Form>

                    <!-- Divider -->
                    <div class="my-7 border-t border-gray-100" />

                    <!-- Change Password heading -->
                    <div class="mb-5">
                        <h3 class="text-[14px] font-semibold text-gray-800">Change Password</h3>
                        <p class="mt-0.5 text-[12.5px] text-gray-400">Update your password regularly to keep your account secure</p>
                    </div>

                    <!-- Password form -->
                    <Form
                        v-bind="updatePassword.form()"
                        v-slot="{ errors, processing: pwProcessing, recentlySuccessful: pwSaved }"
                        class="space-y-4"
                    >
                        <!-- Current password -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-gray-700">Current Password</label>
                            <div class="relative max-w-sm">
                                <Lock class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
                                <input
                                    :type="showCurrent ? 'text' : 'password'"
                                    name="current_password"
                                    autocomplete="current-password"
                                    placeholder="Your current password"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-10 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                />
                                <button type="button" @click="showCurrent = !showCurrent"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <EyeOff v-if="showCurrent" class="size-[15px]" />
                                    <Eye v-else class="size-[15px]" />
                                </button>
                            </div>
                            <InputError :message="errors.current_password" />
                        </div>

                        <!-- New + Confirm row -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">New Password</label>
                                <div class="relative">
                                    <KeyRound class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
                                    <input
                                        :type="showNew ? 'text' : 'password'"
                                        name="password"
                                        autocomplete="new-password"
                                        placeholder="New password"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-10 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <button type="button" @click="showNew = !showNew"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <EyeOff v-if="showNew" class="size-[15px]" />
                                        <Eye v-else class="size-[15px]" />
                                    </button>
                                </div>
                                <InputError :message="errors.password" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Confirm New Password</label>
                                <div class="relative">
                                    <KeyRound class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
                                    <input
                                        :type="showConfirm ? 'text' : 'password'"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        placeholder="Confirm new password"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-10 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <EyeOff v-if="showConfirm" class="size-[15px]" />
                                        <Eye v-else class="size-[15px]" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button
                                type="submit"
                                :disabled="pwProcessing"
                                class="flex items-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Lock class="size-3.5" />
                                Update Password
                            </button>
                            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                                <span v-if="pwSaved" class="text-[12.5px] font-medium text-green-600">Password updated!</span>
                            </Transition>
                        </div>
                    </Form>

                </div>
            </div>
        </div>
    </div>
</template>
