<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Clock,
    Edit2,
    Plus,
    RefreshCw,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import InputError from '@/components/InputError.vue';
import TomSelectInput from '@/components/TomSelectInput.vue';
import { getInitials } from '@/composables/useInitials';

defineOptions({ layout: DashboardLayout });

// ── Types ──────────────────────────────────────────────────────────────────
type Priority = 'low' | 'medium' | 'high' | 'critical';

interface TaskStatusItem {
    id: number;
    name: string;
    slug: string;
    color: string;
    is_done: boolean;
}

interface ProjectOption {
    id: number;
    name: string;
    color: string;
}

interface Assignee {
    id: number;
    name: string;
}

interface TaskItem {
    id: number;
    title: string;
    description: string | null;
    priority: Priority;
    deadline: string | null;
    deadlineFormatted: string | null;
    completed_at: string | null;
    completedAtFormatted: string | null;
    project_id: number;
    task_status_id: number;
    project: ProjectOption;
    status: TaskStatusItem;
    assignee: Assignee | null;
    updated_at: string;
}

interface TaskStats {
    assigned: number;
    inProgress: number;
    overdue: number;
    completed: number;
}

// ── Props ───────────────────────────────────────────────────────────────────
const props = defineProps<{
    tasks: TaskItem[];
    stats: TaskStats;
    taskStatuses: TaskStatusItem[];
    projects: ProjectOption[];
    today: string;
}>();

// ── Stat cards ──────────────────────────────────────────────────────────────
const statCards = [
    {
        label: 'Assigned Tasks',
        key: 'assigned' as const,
        icon: ClipboardList,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        valuColor: 'text-gray-900',
    },
    {
        label: 'In Progress',
        key: 'inProgress' as const,
        icon: RefreshCw,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        valuColor: 'text-gray-900',
    },
    {
        label: 'Overdue Tasks',
        key: 'overdue' as const,
        icon: AlertTriangle,
        iconBg: 'bg-red-100',
        iconColor: 'text-red-500',
        valuColor: 'text-red-600',
    },
    {
        label: 'Completed',
        key: 'completed' as const,
        icon: CheckCircle2,
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
        valuColor: 'text-gray-900',
    },
];

// ── Filter state ────────────────────────────────────────────────────────────
const searchQuery    = ref('');
const statusFilter   = ref('');
const priorityFilter = ref('');
const projectFilter  = ref('');
const sortBy         = ref<'deadline' | 'priority' | 'updated'>('deadline');

const PRIORITY_WEIGHT: Record<Priority, number> = {
    critical: 4, high: 3, medium: 2, low: 1,
};

const filteredTasks = computed(() => {
    let result = [...props.tasks];

    if (statusFilter.value)
        result = result.filter(t => t.task_status_id === Number(statusFilter.value));
    if (priorityFilter.value)
        result = result.filter(t => t.priority === priorityFilter.value);
    if (projectFilter.value)
        result = result.filter(t => t.project_id === Number(projectFilter.value));
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(
            t =>
                t.title.toLowerCase().includes(q) ||
                (t.description ?? '').toLowerCase().includes(q) ||
                t.project.name.toLowerCase().includes(q),
        );
    }

    switch (sortBy.value) {
        case 'deadline':
            result.sort((a, b) => (a.deadline ?? 'z').localeCompare(b.deadline ?? 'z'));
            break;
        case 'priority':
            result.sort((a, b) => PRIORITY_WEIGHT[b.priority] - PRIORITY_WEIGHT[a.priority]);
            break;
        case 'updated':
            result.sort((a, b) => b.updated_at.localeCompare(a.updated_at));
            break;
    }

    return result;
});

const overdueTasks = computed(() =>
    props.tasks
        .filter(t => t.deadline !== null && t.deadline < props.today && !t.status.is_done)
        .sort((a, b) => a.deadline!.localeCompare(b.deadline!)),
);

const uniqueProjects = computed(() =>
    props.projects.filter(p => props.tasks.some(t => t.project_id === p.id)),
);

// ── Badge helpers ───────────────────────────────────────────────────────────
function statusBadgeClass(slug: string): string {
    const map: Record<string, string> = {
        backlog:      'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200',
        todo:         'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-300',
        in_progress:  'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
        code_review:  'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
        testing:      'bg-fuchsia-50 text-fuchsia-700 ring-1 ring-inset ring-fuchsia-200',
        done:         'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
    };
    return map[slug] ?? 'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200';
}

function priorityBadgeClass(priority: Priority): string {
    const map: Record<Priority, string> = {
        low:      'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200',
        medium:   'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
        high:     'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
        critical: 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
    };
    return map[priority];
}

function isOverdue(deadline: string | null): boolean {
    return deadline !== null && deadline < props.today;
}

function isCompletedLate(task: TaskItem): boolean {
    return task.status.is_done
        && task.deadline !== null
        && task.completed_at !== null
        && task.completed_at > task.deadline;
}

function capitalize(s: string): string {
    return s.charAt(0).toUpperCase() + s.slice(1);
}

// ── Inline status dropdown ──────────────────────────────────────────────────
const openStatusDropdownId = ref<number | null>(null);

function updateTaskStatus(task: TaskItem, status: TaskStatusItem) {
    openStatusDropdownId.value = null;
    router.patch(
        `/tasks/${task.id}/status`,
        { task_status_id: status.id },
        { preserveScroll: true },
    );
}

// ── Modal state ─────────────────────────────────────────────────────────────
const showTaskModal   = ref(false);
const showDetailModal = ref(false);
const editingTask     = ref<TaskItem | null>(null);
const viewingTask     = ref<TaskItem | null>(null);

const taskForm = useForm({
    title:          '',
    description:    '',
    priority:       'medium' as Priority,
    project_id:     '' as number | '',
    task_status_id: '' as number | '',
    deadline:       '',
});

const projectOptions = computed(() =>
    props.projects.map(p => ({ value: p.id, label: p.name })),
);

function openCreateModal() {
    editingTask.value = null;
    taskForm.reset();
    taskForm.clearErrors();
    showTaskModal.value = true;
}

function openEditModal(task: TaskItem) {
    editingTask.value       = task;
    taskForm.title          = task.title;
    taskForm.description    = task.description ?? '';
    taskForm.priority       = task.priority;
    taskForm.project_id     = task.project_id;
    taskForm.task_status_id = task.task_status_id;
    taskForm.deadline       = task.deadline ?? '';
    taskForm.clearErrors();
    showTaskModal.value = true;
}

function submitTaskForm() {
    if (editingTask.value) {
        taskForm.put(`/tasks/${editingTask.value.id}`, {
            onSuccess: () => {
                showTaskModal.value = false;
            },
        });
    } else {
        taskForm.post('/tasks', {
            onSuccess: () => {
                showTaskModal.value = false;
            },
        });
    }
}

function openDetailModal(task: TaskItem) {
    viewingTask.value     = task;
    showDetailModal.value = true;
}

function openEditFromDetail(task: TaskItem) {
    showDetailModal.value = false;
    openEditModal(task);
}

function deleteTask(task: TaskItem) {
    if (!confirm(`Delete "${task.title}"? This cannot be undone.`)) return;
    router.delete(`/tasks/${task.id}`);
}
</script>

<template>
    <Head title="My Tasks" />

    <!-- Transparent backdrop — closes status dropdown on outside click -->
    <div
        v-if="openStatusDropdownId !== null"
        class="fixed inset-0 z-10"
        @click="openStatusDropdownId = null"
    />

    <!-- ── Page header ───────────────────────────────────────────────────── -->
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">My Tasks</h1>
            <p class="mt-1 text-sm text-gray-500">All tasks assigned to you across projects</p>
        </div>
        <button
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95"
            @click="openCreateModal"
        >
            <Plus class="size-4" />
            Create Task
        </button>
    </div>

    <!-- ── Stats grid ─────────────────────────────────────────────────────── -->
    <div class="mb-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            v-for="card in statCards"
            :key="card.key"
            class="rounded-xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-400">{{ card.label }}</p>
                    <p :class="['mt-1.5 text-3xl font-bold tracking-tight', card.valuColor]">
                        {{ stats[card.key] }}
                    </p>
                </div>
                <span :class="['flex h-10 w-10 items-center justify-center rounded-lg', card.iconBg, card.iconColor]">
                    <component :is="card.icon" class="size-5" />
                </span>
            </div>
        </div>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────────────────── -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative min-w-[200px] max-w-xs flex-1">
            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search tasks..."
                class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
            />
        </div>

        <!-- Status filter -->
        <select
            v-model="statusFilter"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 outline-none transition hover:border-gray-300 focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
        >
            <option value="">All Status</option>
            <option v-for="s in taskStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>

        <!-- Priority filter -->
        <select
            v-model="priorityFilter"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 outline-none transition hover:border-gray-300 focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
        >
            <option value="">All Priority</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
        </select>

        <!-- Project filter -->
        <select
            v-model="projectFilter"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 outline-none transition hover:border-gray-300 focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
        >
            <option value="">All Projects</option>
            <option v-for="p in uniqueProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>

        <!-- Sort -->
        <select
            v-model="sortBy"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 outline-none transition hover:border-gray-300 focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
        >
            <option value="deadline">Sort: Deadline</option>
            <option value="priority">Sort: Priority</option>
            <option value="updated">Sort: Recently Updated</option>
        </select>

        <!-- Count -->
        <span class="ml-auto text-xs text-gray-400">
            Showing <strong class="text-gray-700">{{ filteredTasks.length }}</strong> of
            <strong class="text-gray-700">{{ tasks.length }}</strong> tasks
        </span>
    </div>

    <!-- ── Task table ─────────────────────────────────────────────────────── -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <p class="text-[15px] font-semibold text-gray-900">All Tasks</p>
                <p class="mt-0.5 text-xs text-gray-400">Tasks assigned to you across all projects</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Task</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Project</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Priority</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Status</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Deadline</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="task in filteredTasks"
                        :key="task.id"
                        class="border-b border-gray-50 transition-colors last:border-0 hover:bg-gray-50/60"
                    >
                        <!-- Task -->
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-800">{{ task.title }}</p>
                            <p v-if="task.description" class="mt-0.5 line-clamp-1 text-xs text-gray-400">
                                {{ task.description }}
                            </p>
                        </td>

                        <!-- Project -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2 w-2 shrink-0 rounded-full"
                                    :style="{ background: task.project.color }"
                                />
                                <span class="text-gray-600">{{ task.project.name }}</span>
                            </div>
                        </td>

                        <!-- Priority -->
                        <td class="px-5 py-3.5">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                                    priorityBadgeClass(task.priority),
                                ]"
                            >
                                {{ capitalize(task.priority) }}
                            </span>
                        </td>

                        <!-- Status — inline dropdown -->
                        <td class="px-5 py-3.5">
                            <div class="relative inline-block">
                                <!-- Clickable status badge -->
                                <button
                                    :class="[
                                        'inline-flex cursor-pointer items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold transition',
                                        statusBadgeClass(task.status.slug),
                                    ]"
                                    @click.stop="openStatusDropdownId = openStatusDropdownId === task.id ? null : task.id"
                                >
                                    {{ task.status.name }}
                                    <ChevronDown class="size-3 opacity-60" />
                                </button>

                                <!-- Status options dropdown -->
                                <div
                                    v-if="openStatusDropdownId === task.id"
                                    class="absolute left-0 top-full z-20 mt-1 min-w-[160px] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                                >
                                    <button
                                        v-for="s in taskStatuses"
                                        :key="s.id"
                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-xs text-gray-700 transition hover:bg-gray-50"
                                        :class="{ 'bg-blue-50 font-semibold text-blue-700': s.id === task.task_status_id }"
                                        @click.stop="updateTaskStatus(task, s)"
                                    >
                                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ background: s.color }" />
                                        {{ s.name }}
                                        <Check
                                            v-if="s.id === task.task_status_id"
                                            class="ml-auto size-3 text-blue-600"
                                        />
                                    </button>
                                </div>
                            </div>
                        </td>

                        <!-- Deadline -->
                        <td class="px-5 py-3.5">
                            <!-- Done + Terlambat: amber -->
                            <div v-if="isCompletedLate(task)" class="flex items-center gap-1">
                                <Clock class="size-3.5 shrink-0 text-amber-500" />
                                <div>
                                    <span class="whitespace-nowrap text-sm font-semibold text-amber-600">
                                        {{ task.completedAtFormatted ?? '—' }}
                                    </span>
                                    <p class="text-[10px] font-medium text-amber-500">Terlambat</p>
                                </div>
                            </div>
                            <!-- Done + Tepat Waktu: emerald -->
                            <div v-else-if="task.status.is_done" class="flex items-center gap-1">
                                <CheckCircle2 class="size-3.5 shrink-0 text-emerald-500" />
                                <span class="whitespace-nowrap text-sm font-semibold text-emerald-600">
                                    {{ task.completedAtFormatted ?? '—' }}
                                </span>
                            </div>
                            <!-- Not done: logika existing (merah jika overdue) -->
                            <span
                                v-else-if="task.deadlineFormatted"
                                :class="[
                                    'whitespace-nowrap text-sm',
                                    isOverdue(task.deadline) ? 'font-semibold text-red-600' : 'text-gray-500',
                                ]"
                            >
                                {{ task.deadlineFormatted }}
                            </span>
                            <span v-else class="text-xs text-gray-300">—</span>
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <button
                                    class="inline-flex h-7 items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                    @click="openDetailModal(task)"
                                >
                                    View
                                </button>
                                <button
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600"
                                    title="Edit task"
                                    @click="openEditModal(task)"
                                >
                                    <Edit2 class="size-3.5" />
                                </button>
                                <button
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                    title="Delete task"
                                    @click="deleteTask(task)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="filteredTasks.length === 0">
                        <td colspan="6" class="py-12 text-center text-sm text-gray-400">
                            No tasks match your filters. Try adjusting your criteria.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Overdue section ───────────────────────────────────────────────── -->
    <div
        v-if="overdueTasks.length > 0"
        class="mt-6 overflow-hidden rounded-xl border border-red-200 bg-white"
    >
        <div class="flex items-center gap-2.5 border-b border-red-200 bg-red-50 px-5 py-3.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600">
                <AlertTriangle class="size-4" />
            </span>
            <span class="text-sm font-semibold text-red-800">Overdue Tasks</span>
            <span class="text-xs text-red-600">
                — {{ overdueTasks.length }} task{{ overdueTasks.length > 1 ? 's' : '' }} need attention
            </span>
        </div>
        <div
            v-for="task in overdueTasks"
            :key="`overdue-${task.id}`"
            class="flex items-center justify-between gap-4 border-b border-gray-50 px-5 py-3.5 last:border-0 transition hover:bg-red-50/30"
        >
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-800">{{ task.title }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ task.project.name }}</p>
            </div>
            <div class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                <AlertTriangle class="size-3" />
                Due {{ task.deadlineFormatted }}
            </div>
        </div>
    </div>

    <!-- ── Create / Edit Modal ───────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showTaskModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showTaskModal = false"
            >
                <Transition
                    enter-active-class="transition duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="showTaskModal"
                        class="w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ editingTask ? 'Edit Task' : 'Create Task' }}
                            </h2>
                            <button
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                @click="showTaskModal = false"
                            >
                                <X class="size-4" />
                            </button>
                        </div>

                        <!-- Form -->
                        <form class="space-y-4 px-6 py-5" @submit.prevent="submitTaskForm">
                            <!-- Title -->
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="taskForm.title"
                                    type="text"
                                    placeholder="Task title"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-800 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
                                />
                                <InputError :message="taskForm.errors.title" class="mt-1" />
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="taskForm.description"
                                    rows="3"
                                    placeholder="Optional description..."
                                    class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
                                />
                                <InputError :message="taskForm.errors.description" class="mt-1" />
                            </div>

                            <!-- Priority + Project (2 col) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Priority <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="taskForm.priority"
                                        class="h-9 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
                                    >
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                    <InputError :message="taskForm.errors.priority" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Project
                                        <span v-if="!editingTask" class="text-red-500">*</span>
                                        <span v-else class="ml-1 text-xs font-normal text-gray-400">(cannot change)</span>
                                    </label>
                                    <!-- Tom Select — searchable, disabled when editing -->
                                    <TomSelectInput
                                        v-model="taskForm.project_id"
                                        :options="projectOptions"
                                        placeholder="Select project..."
                                        :disabled="!!editingTask"
                                    />
                                    <InputError :message="taskForm.errors.project_id" class="mt-1" />
                                </div>
                            </div>

                            <!-- Status + Deadline (2 col) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="taskForm.task_status_id"
                                        class="h-9 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
                                    >
                                        <option value="">Select status</option>
                                        <option v-for="s in taskStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <InputError :message="taskForm.errors.task_status_id" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Deadline</label>
                                    <input
                                        v-model="taskForm.deadline"
                                        type="date"
                                        class="h-9 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-50"
                                    />
                                    <InputError :message="taskForm.errors.deadline" class="mt-1" />
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                                <button
                                    type="button"
                                    class="h-9 rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50"
                                    @click="showTaskModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="taskForm.processing"
                                    class="h-9 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                >
                                    {{ editingTask ? 'Save Changes' : 'Create Task' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Task Detail Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDetailModal && viewingTask"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showDetailModal = false"
            >
                <Transition
                    enter-active-class="transition duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="showDetailModal && viewingTask"
                        class="w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl"
                    >
                        <!-- Header -->
                        <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                            <div class="flex-1 pr-4">
                                <h2 class="text-base font-semibold text-gray-900">{{ viewingTask.title }}</h2>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                                            priorityBadgeClass(viewingTask.priority),
                                        ]"
                                    >
                                        {{ capitalize(viewingTask.priority) }}
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                                            statusBadgeClass(viewingTask.status.slug),
                                        ]"
                                    >
                                        {{ viewingTask.status.name }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 px-3 text-xs font-medium text-gray-600 hover:bg-gray-50"
                                    @click="openEditFromDetail(viewingTask)"
                                >
                                    <Edit2 class="size-3.5" />
                                    Edit
                                </button>
                                <button
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                    @click="showDetailModal = false"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="space-y-4 px-6 py-5">
                            <!-- Description -->
                            <div v-if="viewingTask.description">
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Description</p>
                                <p class="leading-relaxed text-sm text-gray-700">{{ viewingTask.description }}</p>
                            </div>

                            <!-- Details grid -->
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                                <!-- Project -->
                                <div>
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Project</p>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :style="{ background: viewingTask.project.color }"
                                        />
                                        <span class="text-sm text-gray-700">{{ viewingTask.project.name }}</span>
                                    </div>
                                </div>

                                <!-- Deadline / Completed -->
                                <div>
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        {{ viewingTask.status.is_done ? 'Completed' : 'Deadline' }}
                                    </p>
                                    <!-- Done state: tanggal selesai -->
                                    <div v-if="viewingTask.status.is_done">
                                        <!-- Terlambat -->
                                        <div v-if="isCompletedLate(viewingTask)" class="flex items-center gap-1.5">
                                            <Clock class="size-3.5 text-amber-500" />
                                            <p class="text-sm font-medium text-amber-600">
                                                {{ viewingTask.completedAtFormatted ?? '—' }}
                                            </p>
                                        </div>
                                        <!-- Tepat Waktu -->
                                        <div v-else class="flex items-center gap-1.5">
                                            <CheckCircle2 class="size-3.5 text-emerald-500" />
                                            <p class="text-sm font-medium text-emerald-600">
                                                {{ viewingTask.completedAtFormatted ?? '—' }}
                                            </p>
                                        </div>
                                        <!-- Badge status penyelesaian -->
                                        <span
                                            v-if="isCompletedLate(viewingTask)"
                                            class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200"
                                        >
                                            <Clock class="size-2.5" />
                                            Diselesaikan Terlambat
                                        </span>
                                        <span
                                            v-else-if="viewingTask.deadline"
                                            class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200"
                                        >
                                            <Check class="size-2.5" />
                                            Tepat Waktu
                                        </span>
                                    </div>
                                    <!-- Non-done state: logika existing -->
                                    <p
                                        v-else
                                        :class="[
                                            'text-sm font-medium',
                                            viewingTask.deadline && isOverdue(viewingTask.deadline)
                                                ? 'text-red-600'
                                                : 'text-gray-700',
                                        ]"
                                    >
                                        {{ viewingTask.deadlineFormatted ?? '—' }}
                                        <span
                                            v-if="viewingTask.deadline && isOverdue(viewingTask.deadline)"
                                            class="ml-1 text-xs font-normal text-red-500"
                                        >(overdue)</span>
                                    </p>
                                </div>

                                <!-- Assignee -->
                                <div>
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Assignee</p>
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-700">
                                            {{ getInitials(viewingTask.assignee?.name) }}
                                        </span>
                                        <span class="text-sm text-gray-700">{{ viewingTask.assignee?.name ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end border-t border-gray-100 px-6 py-3">
                            <button
                                class="h-9 rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50"
                                @click="showDetailModal = false"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
