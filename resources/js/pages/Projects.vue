<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
    Lock,
    Plus,
    Search,
    Trash2,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import InputError from '@/components/InputError.vue';
import TomSelectInput from '@/components/TomSelectInput.vue';
import TaskDetailModal from '@/components/TaskDetailModal.vue';

defineOptions({ layout: DashboardLayout });

// ── Types ──────────────────────────────────────────────────────────────────
type ProjectStatus = 'planning' | 'active' | 'maintenance' | 'completed' | 'archived';
type MemberRole    = 'project_manager' | 'developer' | 'qa' | 'designer' | 'viewer';
type SlideOverTab  = 'details' | 'members' | 'tasks';

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

interface ProjectMember {
    id: number;
    name: string;
    initials: string;
    role: MemberRole;
    joinedAt: string | null;
    isCreator: boolean;
}

interface TaskStatusOption {
    id: number;
    name: string;
    color: string;
    is_done: boolean;
}

interface TaskStatusInfo {
    name: string;
    color: string;
    is_done: boolean;
}

interface MemberTask {
    id: number;
    title: string;
    priority: string;
    deadline: string | null;
    status: TaskStatusInfo | null;
    assignorName: string;
    assignedAt: string | null;
    comment_count: number;
}

interface MemberTaskGroup {
    userId: number | null;
    name: string;
    initials: string;
    tasks: MemberTask[];
}

interface StatusSummaryItem {
    name: string;
    color: string;
    is_done: boolean;
    count: number;
}

interface ProjectItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    color: string;
    status: ProjectStatus;
    progress: number;
    doneCount: number;
    createdBy: number;
    start_date: string | null;
    deadline: string | null;
    deadlineFormatted: string | null;
    taskCount: number;
    statusSummary: StatusSummaryItem[];
    tasksByMember: MemberTaskGroup[];
    members: ProjectMember[];
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
}

interface WorkspaceUser {
    id: number;
    name: string;
    initials: string;
    job_title: string | null;
}

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
    projects: ProjectItem[];
    stats: ProjectStats;
    filters: ProjectFilters;
    workspaceUsers: WorkspaceUser[];
    taskStatuses: TaskStatusOption[];
}>();

// ── Constants ──────────────────────────────────────────────────────────────
const COLOR_SWATCHES: string[] = [
    '#3b6ff8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6b7280',
];

const ROLE_LABELS: Record<MemberRole, string> = {
    project_manager: 'Project Manager',
    developer:       'Developer',
    qa:              'QA',
    designer:        'Designer',
    viewer:          'Viewer',
};

const ROLE_LABELS_NO_PM: Record<string, string> = {
    developer: 'Developer',
    qa:        'QA',
    designer:  'Designer',
    viewer:    'Viewer',
};

const ROLE_COLORS: Record<MemberRole, string> = {
    project_manager: 'bg-blue-100 text-blue-700',
    developer:       'bg-green-100 text-green-700',
    qa:              'bg-purple-100 text-purple-700',
    designer:        'bg-pink-100 text-pink-700',
    viewer:          'bg-gray-100 text-gray-500',
};

const statCards = [
    { label: 'Total Projects', key: 'total'        as const, icon: FolderKanban, iconBg: 'bg-blue-100',    iconColor: 'text-blue-600'   },
    { label: 'Active',         key: 'active'       as const, icon: CheckCircle2, iconBg: 'bg-green-100',   iconColor: 'text-green-600'  },
    { label: 'Near Deadline',  key: 'nearDeadline' as const, icon: Clock,        iconBg: 'bg-amber-100',   iconColor: 'text-amber-600'  },
    { label: 'Completed',      key: 'completed'    as const, icon: CheckCircle2, iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600'},
];

// ── Auth ───────────────────────────────────────────────────────────────────
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id ?? null);

// ── View state ─────────────────────────────────────────────────────────────
const viewMode = ref<'grid' | 'list'>('grid');

// ── Slide-over state ───────────────────────────────────────────────────────
const showCreateModal  = ref(false);
const showSlideOver    = ref(false);
const showTaskModal    = ref(false);
const selectedProject  = ref<ProjectItem | null>(null);
const slideOverTab     = ref<SlideOverTab>('details');
const slideOverEditing = ref(false);

// ── Task detail modal state ─────────────────────────────────────────────────
const showTaskDetail  = ref(false);
const selectedTask    = ref<MemberTask | null>(null);

function openTaskDetail(task: MemberTask) {
    selectedTask.value   = task;
    showTaskDetail.value = true;
}

function onCommentAdded() {
    if (selectedTask.value && selectedProject.value) {
        selectedTask.value.comment_count++;
    }
}

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

const hasActiveFilters = computed(() => searchInput.value !== '' || statusFilter.value !== '');

function resetFilters() {
    searchInput.value  = '';
    statusFilter.value = '';
    sortOrder.value    = 'updated';
    applyFilters();
}

// ── Create form ────────────────────────────────────────────────────────────
const createForm = useForm<ProjectFormData>({
    name: '', description: '', status: 'planning',
    color: '#3b6ff8', start_date: '', deadline: '',
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
    name: '', description: '', status: 'planning',
    color: '#3b6ff8', start_date: '', deadline: '',
});

function openProject(project: ProjectItem) {
    selectedProject.value  = project;
    slideOverTab.value     = 'details';
    slideOverEditing.value = false;
    showSlideOver.value    = true;
}

function startEditing() {
    if (!selectedProject.value) return;
    const p = selectedProject.value;
    editForm.name        = p.name;
    editForm.description = p.description ?? '';
    editForm.status      = p.status;
    editForm.color       = p.color;
    editForm.start_date  = p.start_date ?? '';
    editForm.deadline    = p.deadline ?? '';
    editForm.clearErrors();
    slideOverEditing.value = true;
}

function submitEdit() {
    if (!selectedProject.value) return;
    editForm.put(`/projects/${selectedProject.value.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            slideOverEditing.value = false;
            refreshSelected();
        },
    });
}

// ── Member management ──────────────────────────────────────────────────────
const addMemberForm = useForm<{ user_id: number | ''; role: MemberRole }>({
    user_id: '',
    role:    'developer',
});

const updatingMemberId = ref<number | null>(null);
const removingMemberId = ref<number | null>(null);

const availableUsers = computed<WorkspaceUser[]>(() => {
    if (!selectedProject.value) return props.workspaceUsers;
    const memberIds = new Set(selectedProject.value.members.map(m => m.id));
    return props.workspaceUsers.filter(u => !memberIds.has(u.id));
});

const availableMemberOptions = computed(() =>
    availableUsers.value.map(u => ({
        value: u.id,
        label: u.name + (u.job_title ? ` — ${u.job_title}` : ''),
    }))
);

const memberTaskOptions = computed(() =>
    selectedProject.value?.members.map(m => ({ value: m.id, label: m.name })) ?? []
);

const isCurrentUserPM = computed(() => {
    if (!selectedProject.value) return false;
    return selectedProject.value.members.some(
        m => m.id === currentUserId.value && m.role === 'project_manager'
    );
});

function addMember() {
    if (!selectedProject.value) return;
    addMemberForm.post(`/projects/${selectedProject.value.id}/members`, {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => {
            addMemberForm.reset();
            refreshSelected();
        },
    });
}

function updateMemberRole(memberId: number, role: string) {
    if (!selectedProject.value) return;
    updatingMemberId.value = memberId;
    router.put(
        `/projects/${selectedProject.value.id}/members/${memberId}`,
        { role },
        {
            preserveState:  true,
            preserveScroll: true,
            onSuccess:  () => refreshSelected(),
            onFinish:   () => { updatingMemberId.value = null; },
        },
    );
}

function removeMember(memberId: number) {
    if (!selectedProject.value) return;
    removingMemberId.value = memberId;
    router.delete(
        `/projects/${selectedProject.value.id}/members/${memberId}`,
        {
            preserveState:  true,
            preserveScroll: true,
            onSuccess:  () => refreshSelected(),
            onFinish:   () => { removingMemberId.value = null; },
        },
    );
}

/** After any server operation, sync selectedProject from fresh props. */
function refreshSelected() {
    if (!selectedProject.value) return;
    const updated = props.projects.find(p => p.id === selectedProject.value!.id);
    if (updated) selectedProject.value = updated;
}

// ── Task creation (from project slide-over) ────────────────────────────────
const taskForm = useForm({
    title:          '',
    task_status_id: '' as number | '',
    priority:       'medium',
    assigned_to:    '' as number | '',
    deadline:       '',
});

function openTaskModal() {
    taskForm.reset();
    taskForm.priority = 'medium';
    showTaskModal.value = true;
}

function submitTask() {
    if (!selectedProject.value) return;
    taskForm.post(`/projects/${selectedProject.value.id}/tasks`, {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => {
            showTaskModal.value = false;
            taskForm.reset();
            taskForm.priority = 'medium';
            refreshSelected();
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

function priorityBadge(priority: string): string {
    const map: Record<string, string> = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        medium:   'bg-yellow-100 text-yellow-700',
        low:      'bg-gray-100 text-gray-500',
    };
    return map[priority] ?? 'bg-gray-100 text-gray-500';
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
            <Plus class="size-4" /> Create Project
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
        <div class="relative min-w-[200px] flex-1 max-w-xs">
            <Search class="absolute left-3 top-1/2 size-[14px] -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchInput"
                type="text"
                placeholder="Search projects..."
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-[13px] text-gray-800 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
            />
        </div>
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
        <select
            v-model="sortOrder"
            class="h-10 rounded-lg border border-gray-200 bg-white px-3 pr-8 text-[13px] text-gray-600 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
        >
            <option value="updated">Recently Updated</option>
            <option value="deadline">By Deadline</option>
            <option value="name">By Name</option>
            <option value="progress">By Progress</option>
        </select>
        <div class="flex overflow-hidden rounded-lg border border-gray-200">
            <button
                :class="['flex h-10 w-10 items-center justify-center transition', viewMode === 'grid' ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-400 hover:bg-gray-50']"
                @click="viewMode = 'grid'"
            ><Grid3X3 class="size-4" /></button>
            <button
                :class="['flex h-10 w-10 items-center justify-center border-l border-gray-200 transition', viewMode === 'list' ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-400 hover:bg-gray-50']"
                @click="viewMode = 'list'"
            ><LayoutList class="size-4" /></button>
        </div>
        <span class="ml-auto text-[12px] text-gray-400">
            <strong class="text-gray-700">{{ projects.length }}</strong> project{{ projects.length !== 1 ? 's' : '' }}
        </span>
        <button
            v-if="hasActiveFilters"
            class="flex items-center gap-1 text-[12px] text-gray-400 transition hover:text-gray-600"
            @click="resetFilters"
        ><X class="size-3" /> Clear filters</button>
    </div>

    <!-- ── Projects Grid ─────────────────────────────────────────────────── -->
    <div v-if="projects.length > 0 && viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="project in projects"
            :key="project.id"
            class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-md"
        >
            <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                    <h3 class="truncate text-[14px] font-semibold text-gray-900">{{ project.name }}</h3>
                </div>
                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusBadge(project.status).classes">
                    {{ statusBadge(project.status).label }}
                </span>
            </div>
            <p class="mb-4 line-clamp-2 flex-1 text-[12.5px] leading-relaxed text-gray-500">
                {{ project.description ?? 'No description provided.' }}
            </p>
            <div class="mb-4 flex items-center gap-2">
                <template v-if="project.manager">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                        {{ project.manager.initials }}
                    </div>
                    <span class="text-[12px] text-gray-600">{{ project.manager.name }}</span>
                </template>
                <span v-else class="text-[12px] text-gray-300">No manager assigned</span>
            </div>
            <div class="mb-4">
                <div class="mb-1.5 flex items-center justify-between">
                    <span class="text-[11.5px] text-gray-400">Progress</span>
                    <span class="text-[12px] font-semibold text-gray-600">
                        {{ project.taskCount > 0 ? `${project.doneCount}/${project.taskCount} done` : 'No tasks' }}
                        <span class="ml-1 text-gray-400">({{ project.progress }}%)</span>
                    </span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: project.progress + '%' }" />
                </div>
                <div v-if="project.statusSummary.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                    <span
                        v-for="s in project.statusSummary"
                        :key="s.name"
                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[10.5px] font-medium"
                        :style="{ backgroundColor: s.color + '20', color: s.color }"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: s.color }" />
                        {{ s.count }} {{ s.name }}
                    </span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-[11.5px] text-gray-400">
                    <span class="flex items-center gap-1">
                        <CheckSquare class="size-3.5" />
                        {{ project.taskCount }} task{{ project.taskCount !== 1 ? 's' : '' }}
                    </span>
                    <span v-if="project.deadlineFormatted" class="flex items-center gap-1">
                        <Calendar class="size-3.5" /> {{ project.deadlineFormatted }}
                    </span>
                </div>
                <button
                    class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                    @click="openProject(project)"
                >Open <ChevronRight class="size-3.5" /></button>
            </div>
        </div>
    </div>

    <!-- ── Projects List ─────────────────────────────────────────────────── -->
    <div v-else-if="projects.length > 0 && viewMode === 'list'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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
                <tr v-for="project in projects" :key="project.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: project.color }" />
                            <span class="font-medium text-gray-900">{{ project.name }}</span>
                        </div>
                        <p v-if="project.description" class="mt-0.5 line-clamp-1 pl-5 text-[11.5px] text-gray-400">{{ project.description }}</p>
                    </td>
                    <td class="px-4 py-3.5">
                        <div v-if="project.manager" class="flex items-center gap-2">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">{{ project.manager.initials }}</div>
                            <span class="text-gray-600">{{ project.manager.name }}</span>
                        </div>
                        <span v-else class="text-gray-300">—</span>
                    </td>
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusBadge(project.status).classes">
                            {{ statusBadge(project.status).label }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-blue-500" :style="{ width: project.progress + '%' }" />
                            </div>
                            <span class="text-[12px] text-gray-500">{{ project.progress }}%</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-gray-400">{{ project.doneCount }}/{{ project.taskCount }} done</p>
                    </td>
                    <td class="px-4 py-3.5 text-gray-500">{{ project.deadlineFormatted ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-gray-500">{{ project.taskCount }}</td>
                    <td class="px-4 py-3.5">
                        <button
                            class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                            @click="openProject(project)"
                        >Open <ChevronRight class="size-3" /></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Empty State ───────────────────────────────────────────────────── -->
    <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-20 text-center">
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
            <FolderOpen class="size-7 text-gray-400" />
        </div>
        <p class="text-[15px] font-semibold text-gray-600">{{ hasActiveFilters ? 'No projects match your filters' : 'No projects yet' }}</p>
        <p class="mt-1 text-[13px] text-gray-400">{{ hasActiveFilters ? 'Try adjusting your search or filter criteria.' : 'Create your first project to get started.' }}</p>
        <button v-if="!hasActiveFilters" class="mt-5 flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700" @click="openCreateModal">
            <Plus class="size-4" /> Create Project
        </button>
        <button v-else class="mt-4 text-[13px] font-medium text-blue-600 hover:underline" @click="resetFilters">Clear filters</button>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- CREATE MODAL                                                        -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showCreateModal = false">
                <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" appear>
                    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <h2 class="text-[15px] font-semibold text-gray-900">Create New Project</h2>
                            <button class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="showCreateModal = false"><X class="size-5" /></button>
                        </div>
                        <form class="max-h-[70vh] overflow-y-auto px-6 py-5" @submit.prevent="submitCreate">
                            <div class="space-y-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Project Name <span class="text-red-500">*</span></label>
                                    <input v-model="createForm.name" type="text" required placeholder="e.g. Website Redesign"
                                           class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                    <InputError :message="createForm.errors.name" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Description</label>
                                    <textarea v-model="createForm.description" rows="3" placeholder="Brief description of the project..."
                                              class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-[13.5px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                    <InputError :message="createForm.errors.description" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Status <span class="text-red-500">*</span></label>
                                        <select v-model="createForm.status" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer">
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
                                            <button v-for="swatch in COLOR_SWATCHES" :key="swatch" type="button" :title="swatch"
                                                    :style="{ backgroundColor: swatch }"
                                                    :class="['h-6 w-6 rounded-full border-2 transition-transform', createForm.color === swatch ? 'border-gray-800 scale-110' : 'border-transparent hover:scale-110']"
                                                    @click="createForm.color = swatch" />
                                        </div>
                                        <InputError :message="createForm.errors.color" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Start Date</label>
                                        <input v-model="createForm.start_date" type="date" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                        <InputError :message="createForm.errors.start_date" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Deadline</label>
                                        <input v-model="createForm.deadline" type="date" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                        <InputError :message="createForm.errors.deadline" />
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50" @click="showCreateModal = false">Cancel</button>
                            <button :disabled="createForm.processing" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60" @click="submitCreate">
                                <Plus class="size-4" /> Create Project
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
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showSlideOver" class="fixed inset-0 z-40 bg-black/40" @click="showSlideOver = false" />
        </Transition>

        <!-- Panel -->
        <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <div v-if="showSlideOver && selectedProject" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[500px] flex-col bg-white shadow-2xl">

                <!-- ── Panel header ──────────────────────────────────────── -->
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: selectedProject.color }" />
                        <h2 class="truncate text-[15px] font-semibold text-gray-900">{{ selectedProject.name }}</h2>
                    </div>
                    <div class="ml-3 flex shrink-0 items-center gap-2">
                        <!-- Edit button — only on details tab, not in edit mode, PM only -->
                        <button
                            v-if="slideOverTab === 'details' && !slideOverEditing && isCurrentUserPM"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-600 transition hover:bg-gray-50"
                            @click="startEditing"
                        ><Edit2 class="size-3.5" /> Edit</button>
                        <!-- Tambah Task button — only on tasks tab, PM only -->
                        <button
                            v-else-if="slideOverTab === 'tasks' && isCurrentUserPM"
                            class="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                            @click="openTaskModal"
                        ><Plus class="size-3.5" /> Tambah Task</button>
                        <button class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="showSlideOver = false">
                            <X class="size-5" />
                        </button>
                    </div>
                </div>

                <!-- ── Tab bar ───────────────────────────────────────────── -->
                <div class="flex border-b border-gray-100 px-6">
                    <button
                        :class="['mr-6 border-b-2 py-3 text-[13px] font-medium transition', slideOverTab === 'details' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-400 hover:text-gray-600']"
                        @click="slideOverTab = 'details'; slideOverEditing = false"
                    >Details</button>
                    <button
                        :class="['mr-6 flex items-center gap-1.5 border-b-2 py-3 text-[13px] font-medium transition', slideOverTab === 'tasks' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-400 hover:text-gray-600']"
                        @click="slideOverTab = 'tasks'"
                    >
                        <CheckSquare class="size-3.5" />
                        Tasks
                        <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-600">
                            {{ selectedProject.taskCount }}
                        </span>
                    </button>
                    <button
                        :class="['flex items-center gap-1.5 border-b-2 py-3 text-[13px] font-medium transition', slideOverTab === 'members' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-400 hover:text-gray-600']"
                        @click="slideOverTab = 'members'"
                    >
                        <Users class="size-3.5" />
                        Members
                        <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-600">
                            {{ selectedProject.members.length }}
                        </span>
                    </button>
                </div>

                <!-- ── Scrollable body ───────────────────────────────────── -->
                <div class="flex-1 overflow-y-auto px-6 py-5">

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- DETAILS TAB                                         -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <template v-if="slideOverTab === 'details'">

                        <!-- View mode -->
                        <template v-if="!slideOverEditing">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11.5px] font-semibold" :class="statusBadge(selectedProject.status).classes">
                                    {{ statusBadge(selectedProject.status).label }}
                                </span>
                                <span class="text-[13px] font-medium text-gray-600">{{ selectedProject.progress }}% complete</span>
                            </div>
                            <div class="mb-6 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: selectedProject.progress + '%' }" />
                            </div>
                            <div class="mb-6">
                                <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Description</p>
                                <p class="text-[13px] leading-relaxed text-gray-700">{{ selectedProject.description ?? '—' }}</p>
                            </div>
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
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">{{ selectedProject.manager.initials }}</div>
                                        <span class="text-[13px] text-gray-700">{{ selectedProject.manager.name }}</span>
                                    </div>
                                    <span v-else class="text-[13px] text-gray-400">None assigned</span>
                                </div>
                            </div>
                        </template>

                        <!-- Edit mode -->
                        <template v-else>
                            <form class="space-y-4" @submit.prevent="submitEdit">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Project Name <span class="text-red-500">*</span></label>
                                    <input v-model="editForm.name" type="text" required class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                    <InputError :message="editForm.errors.name" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[13px] font-semibold text-gray-700">Description</label>
                                    <textarea v-model="editForm.description" rows="3" class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                    <InputError :message="editForm.errors.description" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Status <span class="text-red-500">*</span></label>
                                        <select v-model="editForm.status" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer">
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
                                            <button v-for="swatch in COLOR_SWATCHES" :key="swatch" type="button" :title="swatch"
                                                    :style="{ backgroundColor: swatch }"
                                                    :class="['h-6 w-6 rounded-full border-2 transition-transform', editForm.color === swatch ? 'border-gray-800 scale-110' : 'border-transparent hover:scale-110']"
                                                    @click="editForm.color = swatch" />
                                        </div>
                                        <InputError :message="editForm.errors.color" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Start Date</label>
                                        <input v-model="editForm.start_date" type="date" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                        <InputError :message="editForm.errors.start_date" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[13px] font-semibold text-gray-700">Deadline</label>
                                        <input v-model="editForm.deadline" type="date" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13.5px] text-gray-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                        <InputError :message="editForm.errors.deadline" />
                                    </div>
                                </div>
                                <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-blue-400">Auto-calculated Progress</p>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 overflow-hidden rounded-full bg-blue-100">
                                            <div class="h-full rounded-full bg-blue-500" :style="{ width: selectedProject!.progress + '%' }" />
                                        </div>
                                        <span class="text-[12px] font-semibold text-blue-700">{{ selectedProject!.doneCount }}/{{ selectedProject!.taskCount }} done ({{ selectedProject!.progress }}%)</span>
                                    </div>
                                </div>
                            </form>
                        </template>
                    </template>

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- TASKS TAB                                           -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <template v-else-if="slideOverTab === 'tasks'">

                        <!-- Empty state -->
                        <div v-if="selectedProject.taskCount === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 py-10 text-center">
                            <CheckSquare class="mb-3 size-8 text-gray-300" />
                            <p class="text-[13px] font-medium text-gray-500">No tasks yet</p>
                            <p class="mt-0.5 text-[12px] text-gray-400">Tasks assigned to members will appear here.</p>
                            <button
                                v-if="isCurrentUserPM"
                                class="mt-4 flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[12px] font-semibold text-white transition hover:bg-blue-700"
                                @click="openTaskModal"
                            ><Plus class="size-3.5" /> Tambah Task Pertama</button>
                        </div>

                        <template v-else>
                            <!-- Progress summary -->
                            <div class="mb-5 rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[12px] font-semibold text-gray-700">Overall Progress</span>
                                    <span class="text-[13px] font-bold text-gray-900">{{ selectedProject.doneCount }}/{{ selectedProject.taskCount }} done</span>
                                </div>
                                <div class="mb-3 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: selectedProject.progress + '%' }" />
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="s in selectedProject.statusSummary"
                                        :key="s.name"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                                        :style="{ backgroundColor: s.color + '18' }"
                                    >
                                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: s.color }" />
                                        <span class="text-[11.5px] font-semibold" :style="{ color: s.color }">{{ s.count }}</span>
                                        <span class="text-[11.5px] text-gray-600">{{ s.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tasks grouped by member -->
                            <div class="space-y-5">
                                <div v-for="group in selectedProject.tasksByMember" :key="group.userId ?? 'unassigned'">
                                    <!-- Member header -->
                                    <div class="mb-2 flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                            {{ group.initials }}
                                        </div>
                                        <span class="text-[13px] font-semibold text-gray-800">{{ group.name }}</span>
                                        <span class="text-[11.5px] text-gray-400">· {{ group.tasks.length }} task{{ group.tasks.length !== 1 ? 's' : '' }}</span>
                                        <span class="ml-auto text-[11.5px] text-gray-400">
                                            {{ group.tasks.filter(t => t.status?.is_done).length }} done
                                        </span>
                                    </div>
                                    <!-- Task rows -->
                                    <div class="flex flex-col gap-1.5 pl-9">
                                        <div
                                            v-for="task in group.tasks"
                                            :key="task.id"
                                            class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-100 bg-white px-3 py-2 shadow-sm transition hover:border-blue-200 hover:bg-blue-50/40"
                                            @click="openTaskDetail(task)"
                                        >
                                            <!-- Status dot -->
                                            <span
                                                class="h-2 w-2 shrink-0 rounded-full"
                                                :style="{ backgroundColor: task.status?.color ?? '#9ca3af' }"
                                            />
                                            <!-- Task title + assignor -->
                                            <div class="min-w-0 flex-1 overflow-hidden">
                                                <span
                                                    class="block truncate text-[12.5px]"
                                                    :class="task.status?.is_done ? 'text-gray-400 line-through' : 'text-gray-700'"
                                                >{{ task.title }}</span>
                                                <span class="block truncate text-[10.5px] text-gray-400">
                                                    by {{ task.assignorName }}<template v-if="task.assignedAt"> · {{ task.assignedAt }}</template>
                                                </span>
                                            </div>
                                            <!-- Comment count -->
                                            <span
                                                v-if="task.comment_count > 0"
                                                class="flex shrink-0 items-center gap-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500"
                                            >
                                                <svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                                {{ task.comment_count }}
                                            </span>
                                            <!-- Status badge -->
                                            <span
                                                v-if="task.status"
                                                class="shrink-0 rounded-full px-2 py-0.5 text-[10.5px] font-medium"
                                                :style="{ backgroundColor: task.status.color + '20', color: task.status.color }"
                                            >{{ task.status.name }}</span>
                                            <!-- Priority badge -->
                                            <span
                                                class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-medium capitalize"
                                                :class="priorityBadge(task.priority)"
                                            >{{ task.priority }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </template>
                    </template>

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- MEMBERS TAB                                         -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <template v-else-if="slideOverTab === 'members'">

                        <!-- Current members list -->
                        <div v-if="selectedProject.members.length > 0" class="mb-6">
                            <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                Current Members ({{ selectedProject.members.length }})
                            </p>
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="member in selectedProject.members"
                                    :key="member.id"
                                    class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3"
                                >
                                    <!-- Avatar -->
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white">
                                        {{ member.initials }}
                                    </div>

                                    <!-- Name + joined -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-[13px] font-medium text-gray-800">{{ member.name }}</p>
                                            <Lock v-if="member.isCreator" class="size-3 shrink-0 text-gray-400" title="Project creator — role cannot be changed" />
                                        </div>
                                        <p v-if="member.joinedAt" class="text-[11px] text-gray-400">Joined {{ member.joinedAt }}</p>
                                    </div>

                                    <!-- Role select (disabled for creator or non-PM) -->
                                    <select
                                        :value="member.role"
                                        :disabled="updatingMemberId === member.id || member.isCreator || !isCurrentUserPM"
                                        class="h-8 rounded-lg border border-gray-200 bg-white px-2 pr-7 text-[12px] text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                        @change="(e) => updateMemberRole(member.id, (e.target as HTMLSelectElement).value)"
                                    >
                                        <option v-for="(label, val) in ROLE_LABELS" :key="val" :value="val">{{ label }}</option>
                                    </select>

                                    <!-- Remove button (PM only, hidden for creator) -->
                                    <button
                                        v-if="isCurrentUserPM && !member.isCreator"
                                        :disabled="removingMemberId === member.id"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500 disabled:opacity-40"
                                        :title="`Remove ${member.name}`"
                                        @click="removeMember(member.id)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                    <!-- Spacer to maintain alignment when no remove button -->
                                    <div v-else class="h-8 w-8 shrink-0" />
                                </div>
                            </div>
                        </div>

                        <!-- Empty members state -->
                        <div v-else class="mb-6 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 py-10 text-center">
                            <Users class="mb-3 size-8 text-gray-300" />
                            <p class="text-[13px] font-medium text-gray-500">No members yet</p>
                            <p class="mt-0.5 text-[12px] text-gray-400">Add members from your workspace below.</p>
                        </div>

                        <!-- Add member section (PM only) -->
                        <div v-if="isCurrentUserPM" class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="mb-3 flex items-center gap-1.5 text-[13px] font-semibold text-gray-800">
                                <UserPlus class="size-3.5 text-blue-600" /> Add Member
                            </p>

                            <div v-if="availableUsers.length === 0" class="text-[13px] text-gray-400">
                                All workspace members are already in this project.
                            </div>

                            <template v-else>
                                <div class="mb-3 flex flex-col gap-3">
                                    <!-- Member search (TomSelect) -->
                                    <div>
                                        <TomSelectInput
                                            v-model="addMemberForm.user_id"
                                            :options="availableMemberOptions"
                                            placeholder="Search member..."
                                        />
                                        <InputError :message="addMemberForm.errors.user_id" class="mt-1" />
                                    </div>

                                    <!-- Role select (no Project Manager option) -->
                                    <select
                                        v-model="addMemberForm.role"
                                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 pr-8 text-[13px] text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
                                    >
                                        <option v-for="(label, val) in ROLE_LABELS_NO_PM" :key="val" :value="val">{{ label }}</option>
                                    </select>
                                </div>

                                <button
                                    :disabled="!addMemberForm.user_id || addMemberForm.processing"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="addMember"
                                >
                                    <UserPlus class="size-4" />
                                    Add to Project
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- ── Panel footer (edit mode only) ─────────────────────── -->
                <div v-if="slideOverTab === 'details' && slideOverEditing" class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50" @click="slideOverEditing = false">Cancel</button>
                    <button :disabled="editForm.processing" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60" @click="submitEdit">
                        Save Changes
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ════ TAMBAH TASK MODAL ════ -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showTaskModal"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4"
                @click.self="showTaskModal = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">
                            Tambah Task
                            <span v-if="selectedProject" class="font-normal text-gray-400"> — {{ selectedProject.name }}</span>
                        </h2>
                        <button class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" @click="showTaskModal = false">
                            <X class="size-5" />
                        </button>
                    </div>

                    <!-- Form body -->
                    <div class="px-6 py-5 space-y-4">
                        <!-- Title -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-gray-700">Judul Task <span class="text-red-500">*</span></label>
                            <input
                                v-model="taskForm.title"
                                type="text"
                                placeholder="Masukkan judul task..."
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-900 placeholder:text-gray-400 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                            />
                            <InputError :message="taskForm.errors.title" />
                        </div>

                        <!-- Assignee -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-gray-700">Assign to <span class="text-red-500">*</span></label>
                            <TomSelectInput
                                v-model="taskForm.assigned_to"
                                :options="memberTaskOptions"
                                placeholder="Pilih member..."
                            />
                            <InputError :message="taskForm.errors.assigned_to" />
                        </div>

                        <!-- Priority + Status -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Priority</label>
                                <select
                                    v-model="taskForm.priority"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[13px] font-semibold text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select
                                    v-model="taskForm.task_status_id"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 cursor-pointer"
                                >
                                    <option value="">Pilih status...</option>
                                    <option v-for="s in taskStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                                <InputError :message="taskForm.errors.task_status_id" />
                            </div>
                        </div>

                        <!-- Deadline -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[13px] font-semibold text-gray-700">Deadline</label>
                            <input
                                v-model="taskForm.deadline"
                                type="date"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                            />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                            @click="showTaskModal = false"
                        >Cancel</button>
                        <button
                            :disabled="!taskForm.title || !taskForm.assigned_to || !taskForm.task_status_id || taskForm.processing"
                            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitTask"
                        >
                            <Plus class="size-4" /> Tambah Task
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ════ TASK DETAIL MODAL ════ -->
    <TaskDetailModal
        v-model="showTaskDetail"
        :task="selectedTask"
        :project-name="selectedProject?.name"
        @comment-added="onCommentAdded"
    />
</template>
