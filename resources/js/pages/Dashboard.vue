<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    FolderKanban,
    ListTodo,
    Plus,
} from 'lucide-vue-next';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

interface StatCard {
    activeProjects: number;
    activeProjectsDiff: number;
    tasksInProgress: number;
    tasksInProgressDiff: number;
    overdueTasks: number;
    overdueTasksDiff: number;
    completedThisWeek: number;
    completedThisWeekDiff: number;
}

interface RecentProject {
    id: number;
    name: string;
    color: string;
    status: string;
    progress: number;
    deadline: string | null;
    manager: { name: string; initials: string } | null;
    taskCount: number;
}

interface MyTask {
    id: number;
    title: string;
    priority: string;
    statusName: string;
    statusColor: string;
    projectName: string;
    deadline: string | null;
}

interface ActivityItem {
    id: number;
    description: string;
    action: string;
    createdAt: string;
    user: { name: string; initials: string };
}

const props = defineProps<{
    stats: StatCard;
    recentProjects: RecentProject[];
    myTasks: MyTask[];
    recentActivity: ActivityItem[];
}>();

const statCards = [
    {
        label: 'Active Projects',
        key: 'activeProjects' as const,
        diffKey: 'activeProjectsDiff' as const,
        icon: FolderKanban,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
    },
    {
        label: 'Tasks In Progress',
        key: 'tasksInProgress' as const,
        diffKey: 'tasksInProgressDiff' as const,
        icon: ListTodo,
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
    },
    {
        label: 'Overdue Tasks',
        key: 'overdueTasks' as const,
        diffKey: 'overdueTasksDiff' as const,
        icon: AlertCircle,
        iconBg: 'bg-red-100',
        iconColor: 'text-red-500',
    },
    {
        label: 'Completed This Week',
        key: 'completedThisWeek' as const,
        diffKey: 'completedThisWeekDiff' as const,
        icon: CheckCircle2,
        iconBg: 'bg-green-100',
        iconColor: 'text-green-600',
    },
];

function statusBadge(status: string) {
    const map: Record<string, { label: string; classes: string }> = {
        active:      { label: 'Active',      classes: 'bg-green-100 text-green-700' },
        planning:    { label: 'Planning',    classes: 'bg-yellow-100 text-yellow-700' },
        maintenance: { label: 'Maintenance', classes: 'bg-orange-100 text-orange-700' },
        completed:   { label: 'Completed',   classes: 'bg-gray-100 text-gray-500' },
        on_hold:     { label: 'On Hold',     classes: 'bg-red-100 text-red-600' },
    };
    return map[status] ?? { label: status, classes: 'bg-gray-100 text-gray-500' };
}

function priorityBadge(priority: string) {
    const map: Record<string, { label: string; classes: string }> = {
        critical: { label: 'Critical', classes: 'bg-red-100 text-red-700' },
        high:     { label: 'High',     classes: 'bg-orange-100 text-orange-700' },
        medium:   { label: 'Medium',   classes: 'bg-yellow-100 text-yellow-700' },
        low:      { label: 'Low',      classes: 'bg-gray-100 text-gray-500' },
    };
    return map[priority] ?? { label: priority, classes: 'bg-gray-100 text-gray-500' };
}

function diffLabel(diff: number): string {
    if (diff === 0) return '— same as last week';
    return (diff > 0 ? '+' : '') + diff + ' vs last week';
}

function diffClasses(diff: number, invertColors = false): string {
    if (diff === 0) return 'text-gray-400';
    const positive = diff > 0;
    const good = invertColors ? !positive : positive;
    return good ? 'text-green-600' : 'text-red-500';
}
</script>

<template>
    <Head title="Dashboard" />

    <!-- Page header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-[22px] font-bold text-gray-900">Dashboard</h1>
            <p class="mt-0.5 text-[13px] text-gray-500">Welcome back! Here's what's happening in your workspace.</p>
        </div>
        <button
            disabled
            class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white opacity-60"
        >
            <Plus class="size-4" />
            New Project
        </button>
    </div>

    <!-- Stats grid -->
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            v-for="card in statCards"
            :key="card.label"
            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11.5px] font-semibold uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                    <p class="mt-2 text-[32px] font-bold leading-none text-gray-900">
                        {{ props.stats[card.key] }}
                    </p>
                    <p
                        class="mt-2 text-[11.5px] font-medium"
                        :class="diffClasses(props.stats[card.diffKey], card.key === 'overdueTasks')"
                    >
                        {{ diffLabel(props.stats[card.diffKey]) }}
                    </p>
                </div>
                <span :class="['flex h-10 w-10 items-center justify-center rounded-lg', card.iconBg, card.iconColor]">
                    <component :is="card.icon" class="size-5" />
                </span>
            </div>
        </div>
    </div>

    <!-- Main two-column section -->
    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">

        <!-- Projects Overview -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-[14px] font-semibold text-gray-800">Projects Overview</h2>
                <a href="/projects" class="text-[12px] font-medium text-blue-600 hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3">Project</th>
                            <th class="px-3 py-3">Manager</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Progress</th>
                            <th class="px-3 py-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="project in recentProjects"
                            :key="project.id"
                            class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                                        :style="{ backgroundColor: project.color }"
                                    />
                                    <span class="font-medium text-gray-800">{{ project.name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3.5">
                                <div v-if="project.manager" class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                        {{ project.manager.initials }}
                                    </div>
                                    <span class="hidden text-gray-600 xl:inline">{{ project.manager.name }}</span>
                                </div>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-3 py-3.5">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="statusBadge(project.status).classes"
                                >
                                    {{ statusBadge(project.status).label }}
                                </span>
                            </td>
                            <td class="px-3 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-full rounded-full bg-blue-500 transition-all"
                                            :style="{ width: project.progress + '%' }"
                                        />
                                    </div>
                                    <span class="text-[12px] font-medium text-gray-500">{{ project.progress }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3.5 text-gray-500">
                                {{ project.deadline ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="recentProjects.length === 0">
                            <td colspan="5" class="px-5 py-8 text-center text-[13px] text-gray-400">No active projects</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- My Tasks -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-[14px] font-semibold text-gray-800">My Tasks</h2>
                <a href="/my-tasks" class="text-[12px] font-medium text-blue-600 hover:underline">View all</a>
            </div>
            <div class="flex flex-col divide-y divide-gray-50">
                <div
                    v-for="task in myTasks"
                    :key="task.id"
                    class="flex flex-col gap-1.5 px-5 py-3.5 hover:bg-gray-50/60"
                >
                    <p class="line-clamp-1 text-[13px] font-medium text-gray-800">{{ task.title }}</p>
                    <p class="text-[11.5px] text-gray-400">{{ task.projectName }}</p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10.5px] font-semibold"
                            :class="priorityBadge(task.priority).classes"
                        >
                            {{ priorityBadge(task.priority).label }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10.5px] font-semibold"
                            :style="{ backgroundColor: task.statusColor + '22', color: task.statusColor }"
                        >
                            {{ task.statusName }}
                        </span>
                        <span v-if="task.deadline" class="ml-auto text-[11px] text-gray-400">
                            {{ task.deadline }}
                        </span>
                    </div>
                </div>
                <div v-if="myTasks.length === 0" class="px-5 py-8 text-center text-[13px] text-gray-400">
                    No tasks assigned to you
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-4 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-[14px] font-semibold text-gray-800">Recent Activity</h2>
        </div>
        <div class="flex flex-col divide-y divide-gray-50">
            <div
                v-for="item in recentActivity"
                :key="item.id"
                class="flex items-start gap-3 px-5 py-3.5 hover:bg-gray-50/60"
            >
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10.5px] font-bold text-white">
                    {{ item.user.initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] text-gray-700">
                        <span class="font-semibold text-gray-900">{{ item.user.name }}</span>
                        {{ ' ' + item.description }}
                    </p>
                    <p class="mt-0.5 text-[11.5px] text-gray-400">{{ item.createdAt }}</p>
                </div>
            </div>
            <div v-if="recentActivity.length === 0" class="px-5 py-8 text-center text-[13px] text-gray-400">
                No recent activity
            </div>
        </div>
    </div>
</template>
