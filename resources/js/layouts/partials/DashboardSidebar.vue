<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart2,
    CheckSquare,
    FolderKanban,
    LayoutGrid,
    Settings,
    Users,
    X,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { getInitials } from '@/composables/useInitials';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';

defineProps<{
    isOpen: boolean;
    projectsBadge?: number;
    tasksBadge?: number;
}>();

defineEmits<{
    close: [];
}>();

const page = usePage();
const appName = computed(() => page.props.name as string);
const user = computed(() => (page.props.auth as any).user);
const userInitials = computed(() => getInitials(user.value?.name));

const { isCurrentOrParentUrl } = useCurrentUrl();

const navItems = [
    { label: 'Dashboard',  href: '/dashboard',  icon: LayoutGrid,   badge: null },
    { label: 'Projects',   href: '/projects',   icon: FolderKanban, badge: 'projectsBadge' },
    { label: 'My Tasks',   href: '/my-tasks',   icon: CheckSquare,  badge: 'tasksBadge' },
    { label: 'Team',       href: '/team',       icon: Users,        badge: null },
];

const analyticsItems = [
    { label: 'Reports',  href: '/reports',  icon: BarChart2 },
    { label: 'Settings', href: '/settings', icon: Settings },
];
</script>

<template>
    <!-- Mobile overlay -->
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-20 bg-black/40 lg:hidden"
            @click="$emit('close')"
        />
    </Transition>

    <!-- Sidebar -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-30 flex w-[260px] flex-col border-r border-gray-200 bg-white transition-transform duration-200',
            'lg:relative lg:translate-x-0',
            isOpen ? 'translate-x-0' : '-translate-x-full',
        ]"
    >
        <!-- Logo -->
        <div class="flex h-[60px] items-center gap-3 border-b border-gray-200 px-5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
            </div>
            <span class="text-[15px] font-bold tracking-tight text-gray-900">{{ appName }}</span>

            <!-- Mobile close button -->
            <button
                class="ml-auto flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                @click="$emit('close')"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">

            <!-- Workspace group -->
            <p class="mb-2 px-3 text-[10.5px] font-bold uppercase tracking-[0.8px] text-gray-400">
                Workspace
            </p>

            <div class="mb-5 flex flex-col gap-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13.5px] font-medium transition-all duration-150',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800',
                    ]"
                    @click="$emit('close')"
                >
                    <span
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-md transition-all duration-150',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-blue-100 text-blue-600'
                                : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200 group-hover:text-gray-600',
                        ]"
                    >
                        <component :is="item.icon" class="size-[16px]" />
                    </span>
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge === 'projectsBadge' && projectsBadge"
                        class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-100 px-1.5 text-[11px] font-bold text-blue-700"
                    >
                        {{ projectsBadge }}
                    </span>
                    <span
                        v-else-if="item.badge === 'tasksBadge' && tasksBadge"
                        class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-100 px-1.5 text-[11px] font-bold text-blue-700"
                    >
                        {{ tasksBadge }}
                    </span>
                </Link>
            </div>

            <!-- Analytics group -->
            <p class="mb-2 px-3 text-[10.5px] font-bold uppercase tracking-[0.8px] text-gray-400">
                Analytics
            </p>

            <div class="flex flex-col gap-0.5">
                <Link
                    v-for="item in analyticsItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13.5px] font-medium transition-all duration-150',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800',
                    ]"
                    @click="$emit('close')"
                >
                    <span
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-md transition-all duration-150',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-blue-100 text-blue-600'
                                : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200 group-hover:text-gray-600',
                        ]"
                    >
                        <component :is="item.icon" class="size-[16px]" />
                    </span>
                    <span>{{ item.label }}</span>
                </Link>
            </div>
        </nav>

        <!-- User footer -->
        <div class="border-t border-gray-200 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[12px] font-bold text-white">
                    {{ userInitials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-gray-800">{{ user?.name }}</p>
                    <p class="truncate text-[11.5px] text-gray-400">{{ user?.job_title ?? user?.email }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>
