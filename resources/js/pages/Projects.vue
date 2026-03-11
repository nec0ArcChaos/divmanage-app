<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle2,
    CheckSquare,
    ChevronRight,
    Clock,
    Edit2,
    FolderKanban,
    FolderOpen,
    Grid3X3,
    LayoutList,
    Plus,
    Search,
    X,
} from 'lucide-vue-next';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import InputError from '@/components/InputError.vue';

defineOptions({ layout: DashboardLayout });

// ── Types ──────────────────────────────────────────────────────────────────
type ProjectStatus = 'planning' | 'active' | 'maintenance' | 'completed' | 'archived';

interface ProjectStats {
    total: number;
    active: number;
    nearDeadline: number;
    completed: number;
}

interface ProjectManager {
    name: string;
    initials: string;
}

interface ProjectItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    color: string;
    status: ProjectStatus;
    progress: number;
    start_date: string | null;
    deadline: string | null;
    deadlineFormatted: string | null;
    taskCount: number;
    manager: ProjectManager | null;
}

interface ProjectFilters {
    search: string;
    status: string;
    sort: string;
}

interface ProjectFormData {
    name: string;
    description: string;
    status: ProjectStatus;
    color: string;
    start_date: string;
    deadline: string;
    progress: number;
}

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
    projects: ProjectItem[];
    stats: ProjectStats;
    filters: ProjectFilters;
}>();

// ── Constants ──────────────────────────────────────────────────────────────
const COLOR_SWATCHES = [
    '#3b6ff8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6b7280',
];

const statCards = [
    { label: 'Total Projects',  key: 'total'       as const, icon: FolderKanban,  iconBg: 'bg-blue-100',   iconColor: 'text-blue-600'  },
    { label: 'Active',          key: 'active'      as const, icon: CheckCircle2,  iconBg: 'bg-green-100',  iconColor: 'text-green-600' },
    { label: 'Near Deadline',   key: 'nearDeadline'as const, icon: Clock,         iconBg: 'bg-amber-100',  iconColor: 'text-amber-600' },
    { label: 'Completed',       key: 'completed'   as const, icon: CheckCircle2,  iconBg: 'bg-emerald-100',iconColor: 'text-emerald-600'},
];

// ── View state ─────────────────────────────────────────────────────────────
const viewMode = ref<'grid' | 'list'>('grid');

// ── Modals / Panels ────────────────────────────────────────────────────────
const showCreateModal  = ref(false);
const showSlideOver    = ref(false);
const selectedProject  = ref<ProjectItem | null>(null);
const slideOverEditing = ref(false);

// ── Filter state ───────────────────────────────────────────────────────────
const searchInput  = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const sortOrder    = ref(props.filters.sort);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchInput, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch([statusFilter, sortOrder], () => applyFilters());

function applyFilters() {
    router.get(
        '/projects',
        { search: searchInput.value, status: statusFilter.value, sort: sortOrder.value },
        { preserveState: true, replace: true },
    );
}

const hasActiveFilters = computed(() =>
    searchInput.value !== '' || statusFilter.value !== '',
);

function resetFilters() {
    searchInput.value  = '';
    statusFilter.value = '';
    sortOrder.value    = 'updated';
    applyFilters();
}

// ── Create form ────────────────────────────────────────────────────────────
const createForm = useForm<ProjectFormData>({
    name:        '',
    description: '',
    status:      'planning',
    color:       '#3b6ff8',
    start_date:  '',
    deadline:    '',
    progress:    0,
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/projects', {
        onSuccess: () => { showCreateModal.value = false; },
    });
}

// ── Edit form ──────────────────────────────────────────────────────────────
const editForm = useForm<ProjectFormData>({
    name:        '',
    description: '',
    status:      'planning',
    color:       '#3b6ff8',
    start_date:  '',
    deadline:    '',
    progress:    0,
});

function openProject(project: ProjectItem) {
    selectedProject.value  = project;
    slideOverEditing.value = false;
    showSlideOver.value    = true;
}

function startEditing() {
    if (!selectedProject.value) return;
    editForm.name        = selectedProject.value.name;
    editForm.description = selectedProject.value.description ?? '';
    editForm.status      = selectedProject.value.status;
    editForm.color       = selectedProject.value.color;
    editForm.start_date  = selectedProject.value.start_date ?? '';
    editForm.deadline    = selectedProject.value.deadline ?? '';
    editForm.progress    = selectedProject.value.progress;
    editForm.clearErrors();
    slideOverEditing.value = true;
}

function submitEdit() {
    if (!selectedProject.value) return;
    editForm.put(`/projects/${selectedProject.value.id}`, {
        onSuccess: () => {
            slideOverEditing.value = false;
            showSlideOver.value    = false;
        },
    });
}

// ── Utilities ──────────────────────────────────────────────────────────────
function statusBadge(status: string) {
    const map: Record<string, { label: string; classes: string }> = {
        planning:    { label: 'Planning',    classes: 'bg-purple-100 text-purple-700' },
        active:      { label: 'Active',      classes: 'bg-green-100 text-green-700'  },
        maintenance: { label: 'Maintenance', classes: 'bg-amber-100 text-amber-700'  },
        completed:   { label: 'Completed',   classes: 'bg-gray-100 text-gray-500'    },
        archived:    { label: 'Archived',    classes: 'bg-gray-100 text-gray-400'    },
    };
    return map[status] ?? { label: status, classes: 'bg-gray-100 text-gray-500' };
}
</script>

<template>
    <Head title="Projects" />

    <!-- ── Page Header ──────────────────────────────────────────────────── -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-[22px] font-bold text-gray-900">Projects</h1>
            <p class="mt-0.5 text-[13px] text-gray-500">Manage and track all projects in your workspace.</p>
        </div>
        <button
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700"
            @click="openCreateModal"
        >
            <Plus class="size-4" />
            Create Project
        </button>
    </div>

    <!-- ── Stats Grid ───────────────────────────────────────────────────── -->
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            v-for="card in statCards"
            :key="card.label"
            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11.5px] font-semibold uppercase tracking-wide text-gray-400">{{ card.label }}</p>
                    <p class="mt-2 text-[32px] font-bold leading-none text-gray-900">{{ stats[card.key] }}</p>
                </div>
                <span :class="['flex h-10 w-10 items-center justify-center rounded-lg', card.iconBg, card.iconColor]">
                    <component :is="card.icon" class="size-5" />
                </span>
            </div>
        </div>
    </div>

    <!-- ── Filter Bar ───────────────────────────────────────────────────── -->
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative min-w-[200px] flex-1 max-w-xs">
            <Search class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchInput"
                type="text"
                placeholder="Search projects..."
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-[13px] text-gray-800 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
            />
        </div>

        <!-- Status filter -->
        <select
            v-model="statusFilter"
            class="h-10 rounded-lg border border-gray-200 bg-white px-3 pr-8 text-[13px] text-gray-600 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
        >
            <option value="">All Statuses</option>
            <option value="planning">Planning</option>
            <option value="active">Active</option>
            <option value="maintenance">Maintenance</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
        </select>

        <!-- Sort -->
        <select
            v-model="sortOrder"
            class="h-10 rounded-lg border border-gray-200 bg-white px-3 pr-8 text-[13px] text-gray-600 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
        >
            <option value="updated">Recently Updated</option>
            <option value="deadline">By Deadline</option>
            <option value="name">By Name</option>
            <option value="progress">By Progress</option>
        </select>

        <!-- View toggle -->
        <div class="flex overflow-hidden rounded-lg border border-gray-200">
            <button
                :class="['flex h-10 w-10 items-center justify-center transition', viewMode === 'grid' ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-400 hover:bg-gray-50']"
                @click="viewMode = 'grid'"
            >
                <Grid3X3 class="size-4" />
            </button>
            <button
                :class="['flex h-10 w-10 items-center justify-center border-l border-gray-200 transition', viewMode === 'list' ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-400 hover:bg-gray-50']"
                @click="viewMode = 'list'"
            >
                <LayoutList class="size-4" />
            </button>
        </div>

        <!-- Count -->
        <span class="ml-auto text-[12px] text-gray-400">
            <strong class="text-gray-700">{{ projects.length }}</strong> project{{ projects.length !== 1 ? 's' : '' }}
        </span>

        <!-- Clear filters -->
        <button
            v-if="hasActiveFilters"
            class="flex items-center gap-1 text-[12px] text-gray-400 transition hover:text-gray-600"
            @click="resetFilters"
        >
            <X class="size-3" />
            Clear filters
        </button>
    </div>

    <!-- ── Projects Grid ─────────────────────────────────────────────────── -->
    <div
        v-if="projects.length > 0 && viewMode === 'grid'"
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
    >
        <div
            v-for="project in projects"
            :key="project.id"
            class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-md"
        >
            <!-- Top: color dot + name + badge -->
            <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span
                        class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{ backgroundColor: project.color }"
                    />
                    <h3 class="truncate text-[14px] font-semibold text-gray-900">{{ project.name }}</h3>
                </div>
                <span
                    class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                    :class="statusBadge(project.status).classes"
                >
                    {{ statusBadge(project.status).label }}
                </span>
            </div>

            <!-- Description -->
            <p class="mb-4 line-clamp-2 flex-1 text-[12.5px] leading-relaxed text-gray-500">
                {{ project.description ?? 'No description provided.' }}
            </p>

            <!-- Manager -->
            <div class="mb-4 flex items-center gap-2">
                <template v-if="project.manager">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                        {{ project.manager.initials }}
                    </div>
                    <span class="text-[12px] text-gray-600">{{ project.manager.name }}</span>
                </template>
                <span v-else class="text-[12px] text-gray-300">No manager assigned</span>
            </div>

            <!-- Progress bar -->
            <div class="mb-4">
                <div class="mb-1.5 flex items-center justify-between">
                    <span class="text-[11.5px] text-gray-400">Progress</span>
                    <span class="text-[12px] font-semibold text-gray-600">{{ project.progress }}%</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-blue-500 transition-all duration-500"
                        :style="{ width: project.progress + '%' }"
                    />
                </div>
            </div>

            <!-- Footer: meta + Open button -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-[11.5px] text-gray-400">
                    <span class="flex items-center gap-1">
                        <CheckSquare class="size-3.5" />
                        {{ project.taskCount }} task{{ project.taskCount !== 1 ? 's' : '' }}
                    </span>
                    <span v-if="project.deadlineFormatted" class="flex items-center gap-1">
                        <Calendar class="size-3.5" />
                        {{ project.deadlineFormatted }}
                    </span>
                </div>
                <button
                    class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                    @click="openProject(project)"
                >
                    Open <ChevronRight class="size-3.5" />
                </button>
            </div>
        </div>
    </div>

    <!-- ── Projects List ─────────────────────────────────────────────────── -->
    <div
        v-else-if="projects.length > 0 && viewMode === 'list'"
        class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
    >
        <table class="w-full text-[13px]">
            <thead>
                <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3">Project</th>
                    <th class="px-4 py-3">Manager</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Progress</th>
                    <th class="px-4 py-3">Deadline</th>
                    <th class="px-4 py-3">Tasks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="project in projects"
                    :key="project.id"
                    class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60"
                >
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: project.color }"
                            />
                            <span class="font-medium text-gray-900">{{ project.name }}</span>
                        </div>
                        <p v-if="project.description" class="mt-0.5 line-clamp-1 pl-5 text-[11.5px] text-gray-400">
                            {{ project.description }}
                        </p>
                    </td>
                    <td class="px-4 py-3.5">
                        <div v-if="project.manager" class="flex items-center gap-2">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                {{ project.manager.initials }}
                            </div>
                            <span class="text-gray-600">{{ project.manager.name }}</span>
                        </div>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            :class="statusBadge(project.status).classes"
                        >
                            {{ statusBadge(project.status).label }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full bg-blue-500"
                                    :style="{ width: project.progress + '%' }"
                                />
                            </div>
                            <span class="text-[12px] text-gray-500">{{ project.progress }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-gray-500">{{ project.deadlineFormatted ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-gray-500">{{ project.taskCount }}</td>
                    <td class="px-4 py-3.5">
                        <button
                            class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                            @click="openProject(project)"
                        >
                            Open <ChevronRight class="size-3" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Empty State ───────────────────────────────────────────────────── -->
    <div
        v-else
        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-20 text-center"
    >
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
            <FolderOpen class="size-7 text-gray-400" />
        </div>
        <p class="text-[15px] font-semibold text-gray-600">
            {{ hasActiveFilters ? 'No projects match your filters' : 'No projects yet' }}
        </p>
        <p class="mt-1 text-[13px] text-gray-400">
            {{ hasActiveFilters ? 'Try adjusting your search or filter criteria.' : 'Create your first project to get started.' }}
        </p>
        <button
            v-if="!hasActiveFilters"
            class="mt-5 flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700"
            @click="openCreateModal"
        >
            <Plus class="size-4" />
            Create Project
        </button>
        <button
            v-else
            class="mt-4 text-[13px] font-medium text-blue-600 hover:underline"
            @click="resetFilters"
        >
            Clear filters
        </button>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- CREATE MODAL                                                        -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showCreateModal = false"
            >
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    appear
                >
                    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <h2 class="text-[15px] font-semibold text-gray-900">Create New Project</h2>
                            <button
                                class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                @click="showCreateModal = false"
                            >
                                <X class="size-5" />
                            </button>
                        </div>

                        <!-- Modal form -->
                        <form class="max-h-[70vh] overflow-y-auto px-6 py-5" @submit.prevent="submitCreate">
                            <div class="space-y-4">
                                <!-- Name -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">
                                        Project Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="createForm.name"
                                        type="text"
                                        required
                                        placeholder="e.g. Website Redesign"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <InputError :message="createForm.errors.name" />
                                </div>

                                <!-- Description -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Description</label>
                                    <textarea
                                        v-model="createForm.description"
                                        rows="3"
                                        placeholder="Brief description of the project..."
                                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 resize-none"
                                    />
                                    <InputError :message="createForm.errors.description" />
                                </div>

                                <!-- Status + Color -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">
                                            Status <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="createForm.status"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
                                        >
                                            <option value="planning">Planning</option>
                                            <option value="active">Active</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="completed">Completed</option>
                                            <option value="archived">Archived</option>
                                        </select>
                                        <InputError :message="createForm.errors.status" />
                                    </div>

                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Color</label>
                                        <div class="flex flex-wrap gap-2 pt-1">
                                            <button
                                                v-for="swatch in COLOR_SWATCHES"
                                                :key="swatch"
                                                type="button"
                                                :title="swatch"
                                                :style="{ backgroundColor: swatch }"
                                                :class="[
                                                    'h-6 w-6 rounded-full border-2 transition-transform',
                                                    createForm.color === swatch
                                                        ? 'border-gray-800 scale-110'
                                                        : 'border-transparent hover:scale-110',
                                                ]"
                                                @click="createForm.color = swatch"
                                            />
                                        </div>
                                        <InputError :message="createForm.errors.color" />
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Start Date</label>
                                        <input
                                            v-model="createForm.start_date"
                                            type="date"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                        />
                                        <InputError :message="createForm.errors.start_date" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Deadline</label>
                                        <input
                                            v-model="createForm.deadline"
                                            type="date"
                                            class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                        />
                                        <InputError :message="createForm.errors.deadline" />
                                    </div>
                                </div>

                                <!-- Progress -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">
                                        Initial Progress — {{ createForm.progress }}%
                                    </label>
                                    <input
                                        v-model.number="createForm.progress"
                                        type="range"
                                        min="0"
                                        max="100"
                                        class="w-full accent-blue-600"
                                    />
                                    <InputError :message="createForm.errors.progress" />
                                </div>
                            </div>
                        </form>

                        <!-- Modal footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                                @click="showCreateModal = false"
                            >
                                Cancel
                            </button>
                            <button
                                :disabled="createForm.processing"
                                class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="submitCreate"
                            >
                                <Plus class="size-4" />
                                Create Project
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- PROJECT DETAIL SLIDE-OVER                                          -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showSlideOver"
                class="fixed inset-0 z-40 bg-black/40"
                @click="showSlideOver = false"
            />
        </Transition>

        <!-- Panel -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="showSlideOver && selectedProject"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[480px] flex-col bg-white shadow-2xl"
            >
                <!-- Panel header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="h-3 w-3 shrink-0 rounded-full"
                            :style="{ backgroundColor: selectedProject.color }"
                        />
                        <h2 class="truncate text-[15px] font-semibold text-gray-900">{{ selectedProject.name }}</h2>
                    </div>
                    <div class="ml-3 flex shrink-0 items-center gap-2">
                        <button
                            v-if="!slideOverEditing"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-600 transition hover:bg-gray-50"
                            @click="startEditing"
                        >
                            <Edit2 class="size-3.5" />
                            Edit
                        </button>
                        <button
                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                            @click="showSlideOver = false"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                </div>

                <!-- Scrollable body -->
                <div class="flex-1 overflow-y-auto px-6 py-6">

                    <!-- ── VIEW MODE ──────────────────────────────────── -->
                    <template v-if="!slideOverEditing">
                        <!-- Status + progress -->
                        <div class="mb-4 flex items-center justify-between">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11.5px] font-semibold"
                                :class="statusBadge(selectedProject.status).classes"
                            >
                                {{ statusBadge(selectedProject.status).label }}
                            </span>
                            <span class="text-[13px] font-medium text-gray-600">
                                {{ selectedProject.progress }}% complete
                            </span>
                        </div>

                        <div class="mb-6 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                :style="{ width: selectedProject.progress + '%' }"
                            />
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Description</p>
                            <p class="text-[13px] leading-relaxed text-gray-700">
                                {{ selectedProject.description ?? '—' }}
                            </p>
                        </div>

                        <!-- Meta grid -->
                        <div class="grid grid-cols-2 gap-x-6 gap-y-5">
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Start Date</p>
                                <p class="text-[13px] text-gray-700">{{ selectedProject.start_date ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Deadline</p>
                                <p class="text-[13px] text-gray-700">{{ selectedProject.deadlineFormatted ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Tasks</p>
                                <p class="text-[13px] text-gray-700">{{ selectedProject.taskCount }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Project Manager</p>
                                <div v-if="selectedProject.manager" class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                        {{ selectedProject.manager.initials }}
                                    </div>
                                    <span class="text-[13px] text-gray-700">{{ selectedProject.manager.name }}</span>
                                </div>
                                <span v-else class="text-[13px] text-gray-400">None assigned</span>
                            </div>
                        </div>
                    </template>

                    <!-- ── EDIT MODE ──────────────────────────────────── -->
                    <template v-else>
                        <form class="space-y-4" @submit.prevent="submitEdit">
                            <!-- Name -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">
                                    Project Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                />
                                <InputError :message="editForm.errors.name" />
                            </div>

                            <!-- Description -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Description</label>
                                <textarea
                                    v-model="editForm.description"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 resize-none"
                                />
                                <InputError :message="editForm.errors.description" />
                            </div>

                            <!-- Status + Color -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="editForm.status"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
                                    >
                                        <option value="planning">Planning</option>
                                        <option value="active">Active</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="completed">Completed</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                    <InputError :message="editForm.errors.status" />
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Color</label>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <button
                                            v-for="swatch in COLOR_SWATCHES"
                                            :key="swatch"
                                            type="button"
                                            :title="swatch"
                                            :style="{ backgroundColor: swatch }"
                                            :class="[
                                                'h-6 w-6 rounded-full border-2 transition-transform',
                                                editForm.color === swatch
                                                    ? 'border-gray-800 scale-110'
                                                    : 'border-transparent hover:scale-110',
                                            ]"
                                            @click="editForm.color = swatch"
                                        />
                                    </div>
                                    <InputError :message="editForm.errors.color" />
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Start Date</label>
                                    <input
                                        v-model="editForm.start_date"
                                        type="date"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <InputError :message="editForm.errors.start_date" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Deadline</label>
                                    <input
                                        v-model="editForm.deadline"
                                        type="date"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                    />
                                    <InputError :message="editForm.errors.deadline" />
                                </div>
                            </div>

                            <!-- Progress -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">
                                    Progress — {{ editForm.progress }}%
                                </label>
                                <input
                                    v-model.number="editForm.progress"
                                    type="range"
                                    min="0"
                                    max="100"
                                    class="w-full accent-blue-600"
                                />
                                <InputError :message="editForm.errors.progress" />
                            </div>
                        </form>
                    </template>
                </div>

                <!-- Panel footer (edit mode only) -->
                <div
                    v-if="slideOverEditing"
                    class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        @click="slideOverEditing = false"
                    >
                        Cancel
                    </button>
                    <button
                        :disabled="editForm.processing"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="submitEdit"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
