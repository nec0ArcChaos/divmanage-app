<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Menu, Search, Settings, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { getInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

defineEmits<{
    toggleSidebar: [];
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
const userInitials = computed(() => getInitials(user.value?.name));

const form = useForm({});
const handleLogout = () => form.post(logout().url);
</script>

<template>
    <header class="sticky top-0 z-10 flex h-[60px] shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-5">
        <!-- Hamburger (mobile only) -->
        <button
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
            @click="$emit('toggleSidebar')"
        >
            <Menu class="size-5" />
        </button>

        <!-- Search -->
        <div class="relative max-w-[420px] flex-1">
            <Search class="absolute left-3 top-1/2 size-[15px] -translate-y-1/2 text-gray-400" />
            <input
                type="text"
                placeholder="Search projects, tasks..."
                class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-4 text-[13px] text-gray-700 outline-none placeholder:text-gray-400 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100"
            />
        </div>

        <!-- Right section -->
        <div class="ml-auto flex items-center gap-2">
            <!-- Notification bell -->
            <NotificationBell />

            <!-- User avatar dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-[12px] font-bold text-white ring-2 ring-transparent transition-all hover:ring-blue-200 focus:outline-none">
                        {{ userInitials }}
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuLabel class="font-normal">
                        <div class="flex flex-col gap-0.5">
                            <p class="text-[13px] font-semibold text-gray-800">{{ user?.name }}</p>
                            <p class="truncate text-[11.5px] text-gray-400">{{ user?.email }}</p>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link href="/settings/profile" class="flex cursor-pointer items-center gap-2 text-[13px]">
                            <Settings class="size-[14px] text-gray-400" />
                            Profile Settings
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="cursor-pointer gap-2 text-[13px] text-red-600 focus:bg-red-50 focus:text-red-600"
                        :disabled="form.processing"
                        @click="handleLogout"
                    >
                        <LogOut class="size-[14px]" />
                        Sign Out
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
