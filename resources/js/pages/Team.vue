<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Code2,
    FlaskConical,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import { ScrollArea } from '@/components/ui/scroll-area';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import InputError from '@/components/InputError.vue';
import TomSelectInput from '@/components/TomSelectInput.vue';

defineOptions({ layout: DashboardLayout });

// ── Types ──────────────────────────────────────────────────────────────────
type GlobalRole   = 'admin' | 'project_manager' | 'developer' | 'qa';
type MemberStatus = 'active' | 'on_leave' | 'inactive';

interface MemberProject {
    id: number;
    name: string;
    color: string;
    role: string;
}

interface TeamMember {
    id: number;
    name: string;
    username: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    global_role: GlobalRole;
    status: MemberStatus;
    status_id: number;
    job_title: string | null;
    role_id: number;
    job_id: number | null;
    initials: string;
    projects: MemberProject[];
}

interface ActiveProject {
    id: number;
    name: string;
    color: string;
    members: { name: string; initials: string }[];
}

interface Stats {
    total: number;
    developers: number;
    qa: number;
    projectManagers: number;
}

interface RoleOption      { id: number; slug: string; name: string }
interface StatusOption    { id: number; slug: string; name: string }
interface JobTitleOption  { id: number; name: string }

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
    members: TeamMember[];
    stats: Stats;
    activeProjects: ActiveProject[];
    roles: RoleOption[];
    statuses: StatusOption[];
    jobTitles: JobTitleOption[];
}>();

// ── Auth / Permissions ─────────────────────────────────────────────────────
const page        = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);
const canManage   = computed(() =>
    ['admin', 'project_manager'].includes(currentUser.value?.global_role),
);
const isAdmin = computed(() => currentUser.value?.global_role === 'admin');

// ── Filters ────────────────────────────────────────────────────────────────
const searchQuery  = ref('');
const roleFilter   = ref<'all' | GlobalRole>('all');
const statusFilter = ref<'all' | MemberStatus>('all');

const filteredMembers = computed(() => {
    let result = [...props.members];
    if (roleFilter.value !== 'all')
        result = result.filter(m => m.global_role === roleFilter.value);
    if (statusFilter.value !== 'all')
        result = result.filter(m => m.status === statusFilter.value);
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(
            m =>
                m.name.toLowerCase().includes(q) ||
                m.username.toLowerCase().includes(q) ||
                m.email.toLowerCase().includes(q) ||
                (m.job_title ?? '').toLowerCase().includes(q),
        );
    }
    return result;
});

// ── TomSelect options ──────────────────────────────────────────────────────
const roleOptions = computed(() =>
    props.roles.map(r => ({ value: r.id, label: r.name })),
);
const jobTitleOptions = computed(() =>
    props.jobTitles.map(j => ({ value: j.id, label: j.name })),
);
const statusOptions = computed(() =>
    props.statuses.map(s => ({ value: s.id, label: s.name })),
);

// ── Dropdown ───────────────────────────────────────────────────────────────
const openDropdownId = ref<number | null>(null);
const dropdownMember = ref<TeamMember | null>(null);
const dropdownPos    = ref({ top: 0, left: 0 });

function toggleDropdown(member: TeamMember, event: MouseEvent) {
    event.stopPropagation();
    if (openDropdownId.value === member.id) {
        openDropdownId.value = null;
        return;
    }
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    dropdownPos.value = {
        top:  rect.bottom + 4,
        left: Math.max(8, rect.right - 144),
    };
    dropdownMember.value = member;
    openDropdownId.value  = member.id;
}

function closeDropdown() { openDropdownId.value = null; }

onMounted(()   => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));

// ── View Modal ─────────────────────────────────────────────────────────────
const showViewModal = ref(false);
const viewMember    = ref<TeamMember | null>(null);

function openViewModal(member: TeamMember) {
    viewMember.value   = member;
    showViewModal.value = true;
    closeDropdown();
}

// ── Edit Modal ─────────────────────────────────────────────────────────────
const showEditModal = ref(false);
const editUserId    = ref(0);

const editForm = useForm({
    name:      '',
    username:  '',
    email:     '',
    role_id:   '' as number | '',
    job_id:    '' as number | '',
    status_id: '' as number | '',
    phone:     '',
});

function openEditModal(member: TeamMember) {
    editUserId.value    = member.id;
    editForm.name       = member.name;
    editForm.username   = member.username;
    editForm.email      = member.email;
    editForm.role_id    = member.role_id;
    editForm.job_id     = member.job_id ?? '';
    editForm.status_id  = member.status_id;
    editForm.phone      = member.phone ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
    closeDropdown();
}

function submitEdit() {
    editForm.put(`/team/${editUserId.value}`, {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; },
    });
}

// ── Delete Confirm ─────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const deleteTarget      = ref<TeamMember | null>(null);
const deleting          = ref(false);

function openDeleteConfirm(member: TeamMember) {
    deleteTarget.value      = member;
    showDeleteConfirm.value = true;
    closeDropdown();
}

function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/team/${deleteTarget.value.id}`, {
        onFinish:  () => { deleting.value = false; },
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}

// ── Add Member Modal ───────────────────────────────────────────────────────
const showAddModal = ref(false);

const addForm = useForm({
    name:    '',
    username: '',
    email:   '',
    role_id: '' as number | '',
    job_id:  '' as number | '',
    phone:   '',
});

function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    showAddModal.value = true;
}

function submitAdd() {
    addForm.post('/team', {
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
}

// ── Manage Bidang Modal ────────────────────────────────────────────────────
const showBidangModal = ref(false);
const bidangForm      = useForm({ name: '' });

function openBidangModal() {
    bidangForm.reset();
    bidangForm.clearErrors();
    showBidangModal.value = true;
}

function submitBidang() {
    bidangForm.post('/job-titles', {
        preserveScroll: true,
        onSuccess: () => {
            bidangForm.reset();
            router.reload({ only: ['jobTitles'] });
        },
    });
}

function deleteBidang(id: number) {
    router.delete(`/job-titles/${id}`, { preserveScroll: true });
}

// ── Helpers ────────────────────────────────────────────────────────────────
const roleLabel: Record<GlobalRole, string> = {
    admin:           'Admin',
    project_manager: 'Project Manager',
    developer:       'Developer',
    qa:              'QA',
};

const roleBadgeClass: Record<GlobalRole, string> = {
    admin:           'bg-red-50 text-red-700 ring-1 ring-red-200',
    project_manager: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
    developer:       'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
    qa:              'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
};

const statusLabel: Record<MemberStatus, string> = {
    active:   'Active',
    on_leave: 'On Leave',
    inactive: 'Inactive',
};

const statusBadgeClass: Record<MemberStatus, string> = {
    active:   'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    on_leave: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    inactive: 'bg-gray-100 text-gray-500 ring-1 ring-gray-200',
};

const projectRoleLabel: Record<string, string> = {
    project_manager: 'Project Manager',
    developer:       'Developer',
    qa:              'QA',
};

const avatarColors = [
    'bg-blue-500', 'bg-violet-500', 'bg-emerald-500', 'bg-amber-500',
    'bg-rose-500',  'bg-teal-500',   'bg-indigo-500',  'bg-sky-500',
];
function avatarColor(id: number): string {
    return avatarColors[id % avatarColors.length];
}
</script>

<template>
    <Head title="Team" />

    <div>

        <!-- ── Page Header ──────────────────────────────────────────────── -->
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Team</h1>
                <p class="mt-1 text-sm text-gray-500">Manage and view members of the IT division</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="isAdmin"
                    @click="openBidangModal"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-[13px] font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 active:scale-[0.98]"
                >
                    <Plus class="size-4" />
                    Kelola Bidang
                </button>
                <button
                    v-if="canManage"
                    @click="openAddModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-700 active:scale-[0.98]"
                >
                    <Plus class="size-4" />
                    Add Member
                </button>
            </div>
        </div>

        <!-- ── Stats Grid ───────────────────────────────────────────────── -->
        <div class="mb-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[12.5px] font-medium text-gray-400">Total Members</p>
                        <p class="mt-1.5 text-[28px] font-bold leading-none tracking-tight text-gray-900">{{ stats.total }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <Users class="size-5" />
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[12.5px] font-medium text-gray-400">Developers</p>
                        <p class="mt-1.5 text-[28px] font-bold leading-none tracking-tight text-gray-900">{{ stats.developers }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <Code2 class="size-5" />
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[12.5px] font-medium text-gray-400">QA Engineers</p>
                        <p class="mt-1.5 text-[28px] font-bold leading-none tracking-tight text-gray-900">{{ stats.qa }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <FlaskConical class="size-5" />
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[12.5px] font-medium text-gray-400">Project Managers</p>
                        <p class="mt-1.5 text-[28px] font-bold leading-none tracking-tight text-gray-900">{{ stats.projectManagers }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <ShieldCheck class="size-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filter Bar ───────────────────────────────────────────────── -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[200px] max-w-xs flex-1">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search members..."
                    class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                />
            </div>
            <select
                v-model="roleFilter"
                class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-600 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
            >
                <option value="all">All Roles</option>
                <option value="admin">Admin</option>
                <option value="project_manager">Project Manager</option>
                <option value="developer">Developer</option>
                <option value="qa">QA</option>
            </select>
            <select
                v-model="statusFilter"
                class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-[13px] text-gray-600 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
            >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="on_leave">On Leave</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="ml-auto text-[12.5px] text-gray-400">
                Showing <strong class="font-semibold text-gray-700">{{ filteredMembers.length }}</strong>
                of <strong class="font-semibold text-gray-700">{{ members.length }}</strong> members
            </span>
        </div>

        <!-- ── Team Table ───────────────────────────────────────────────── -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <p class="text-[15px] font-semibold text-gray-900">Team Members</p>
                    <p class="mt-0.5 text-[12.5px] text-gray-400">IT Division staff directory</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Member</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Bidang</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Role</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Projects</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Email</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Status</th>
                            <th class="border-b border-gray-100 px-5 py-2.5 text-left text-[11px] font-bold uppercase tracking-[0.6px] text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="member in filteredMembers"
                            :key="member.id"
                            class="border-b border-gray-50 transition-colors last:border-0 hover:bg-gray-50/60"
                        >
                            <!-- Member -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white', avatarColor(member.id)]">
                                        {{ member.initials }}
                                    </div>
                                    <div>
                                        <p class="text-[13.5px] font-semibold text-gray-800">{{ member.name }}</p>
                                        <p class="mt-0.5 text-[12px] text-gray-400">@{{ member.username }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Bidang -->
                            <td class="px-5 py-3.5">
                                <span class="text-[13px] text-gray-600">{{ member.job_title ?? '—' }}</span>
                            </td>

                            <!-- Role -->
                            <td class="px-5 py-3.5">
                                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-[11.5px] font-semibold', roleBadgeClass[member.global_role]]">
                                    {{ roleLabel[member.global_role] }}
                                </span>
                            </td>

                            <!-- Projects -->
                            <td class="px-5 py-3.5">
                                <p class="text-[13px] text-gray-600">{{ member.projects.length }} project{{ member.projects.length !== 1 ? 's' : '' }}</p>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span
                                        v-for="proj in member.projects.slice(0, 2)"
                                        :key="proj.id"
                                        class="rounded px-1.5 py-0.5 text-[10.5px] font-medium text-gray-500 ring-1 ring-gray-200"
                                    >{{ proj.name }}</span>
                                    <span
                                        v-if="member.projects.length > 2"
                                        class="rounded px-1.5 py-0.5 text-[10.5px] font-medium text-gray-500 ring-1 ring-gray-200"
                                    >+{{ member.projects.length - 2 }}</span>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-5 py-3.5">
                                <span class="text-[13px] text-gray-500">{{ member.email }}</span>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-3.5">
                                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-[11.5px] font-semibold', statusBadgeClass[member.status]]">
                                    {{ statusLabel[member.status] }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="openViewModal(member)"
                                        class="inline-flex h-7 items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 text-[12px] font-medium text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                    >
                                        View
                                    </button>
                                    <button
                                        v-if="canManage"
                                        @click="toggleDropdown(member, $event)"
                                        :class="[
                                            'flex h-7 w-7 items-center justify-center rounded-md border bg-white text-gray-400 transition hover:bg-gray-100 hover:text-gray-600',
                                            openDropdownId === member.id ? 'border-gray-300 bg-gray-100 text-gray-600' : 'border-gray-200',
                                        ]"
                                    >
                                        <MoreHorizontal class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredMembers.length === 0">
                            <td colspan="7" class="py-12 text-center text-[13px] text-gray-400">
                                No members match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Project Assignments ──────────────────────────────────────── -->
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-4">
                <p class="text-[15px] font-semibold text-gray-900">Project Assignments</p>
                <p class="mt-0.5 text-[12.5px] text-gray-400">Team distribution across active projects</p>
            </div>
            <div v-if="activeProjects.length === 0" class="py-10 text-center text-[13px] text-gray-400">
                No active projects.
            </div>
            <div
                v-for="project in activeProjects"
                :key="project.id"
                class="flex items-center justify-between gap-4 border-b border-gray-50 px-5 py-4 transition-colors last:border-0 hover:bg-gray-50/60"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: project.color }" />
                    <div>
                        <p class="text-[14px] font-semibold text-gray-800">{{ project.name }}</p>
                        <p class="mt-0.5 text-[12px] text-gray-400">{{ project.members.length }} member{{ project.members.length !== 1 ? 's' : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div
                        v-for="(member, idx) in project.members.slice(0, 4)"
                        :key="idx"
                        :class="['flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white', idx > 0 ? '-ml-2' : '', avatarColor(idx)]"
                        :title="member.name"
                    >
                        {{ member.initials }}
                    </div>
                    <div
                        v-if="project.members.length > 4"
                        class="-ml-2 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-100 text-[10px] font-semibold text-gray-500"
                    >
                        +{{ project.members.length - 4 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="h-8" />
    </div>

    <!-- ── Dropdown (teleported to avoid overflow clipping) ─────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="openDropdownId !== null && dropdownMember"
                class="fixed z-[200] w-36 overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-gray-200"
                :style="{ top: dropdownPos.top + 'px', left: dropdownPos.left + 'px' }"
                @click.stop
            >
                <button
                    @click="openEditModal(dropdownMember)"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-[13px] text-gray-700 transition hover:bg-gray-50"
                >
                    <Pencil class="size-3.5 text-gray-400" />
                    Edit
                </button>
                <button
                    v-if="isAdmin"
                    @click="openDeleteConfirm(dropdownMember)"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-[13px] text-red-600 transition hover:bg-red-50"
                >
                    <Trash2 class="size-3.5" />
                    Hapus
                </button>
            </div>
        </Transition>
    </Teleport>

    <!-- ── View Member Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showViewModal && viewMember" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showViewModal = false">
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                    <div v-if="showViewModal" class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <h2 class="text-[16px] font-bold text-gray-900">Detail Member</h2>
                            <button @click="showViewModal = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                <X class="size-4" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5">

                            <!-- Avatar + name + badges -->
                            <div class="flex items-center gap-4">
                                <div :class="['flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white', avatarColor(viewMember.id)]">
                                    {{ viewMember.initials }}
                                </div>
                                <div>
                                    <p class="text-[16px] font-bold text-gray-900">{{ viewMember.name }}</p>
                                    <p class="text-[13px] text-gray-400">@{{ viewMember.username }}</p>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-[11.5px] font-semibold', roleBadgeClass[viewMember.global_role]]">
                                            {{ roleLabel[viewMember.global_role] }}
                                        </span>
                                        <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-[11.5px] font-semibold', statusBadgeClass[viewMember.status]]">
                                            {{ statusLabel[viewMember.status] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Info rows -->
                            <dl class="mt-5 space-y-3 rounded-xl bg-gray-50 p-4">
                                <div>
                                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Email</dt>
                                    <dd class="mt-0.5 text-[13.5px] text-gray-700">{{ viewMember.email }}</dd>
                                </div>
                                <div v-if="viewMember.phone">
                                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Nomor HP</dt>
                                    <dd class="mt-0.5 text-[13.5px] text-gray-700">{{ viewMember.phone }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Bidang</dt>
                                    <dd class="mt-0.5 text-[13.5px] text-gray-700">{{ viewMember.job_title ?? '—' }}</dd>
                                </div>
                            </dl>

                            <!-- Projects -->
                            <div class="mt-4">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                                    Projects ({{ viewMember.projects.length }})
                                </p>
                                <div v-if="viewMember.projects.length === 0" class="text-[13px] text-gray-400">
                                    Tidak ada project.
                                </div>
                                <ScrollArea class="h-44">
                                    <div
                                        v-for="proj in viewMember.projects"
                                        :key="proj.id"
                                        class="flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-gray-50"
                                    >
                                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ background: proj.color }" />
                                        <span class="flex-1 text-[13.5px] text-gray-700">{{ proj.name }}</span>
                                        <span class="text-[11.5px] text-gray-400">{{ projectRoleLabel[proj.role] ?? proj.role }}</span>
                                    </div>
                                </ScrollArea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                            <button
                                v-if="canManage"
                                @click="openEditModal(viewMember); showViewModal = false"
                                class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                            >
                                <Pencil class="size-3.5" /> Edit
                            </button>
                            <button @click="showViewModal = false" class="h-9 rounded-lg bg-gray-900 px-4 text-[13px] font-semibold text-white transition hover:bg-gray-700">
                                Tutup
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Edit Member Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showEditModal = false">
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                    <div v-if="showEditModal" class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <div>
                                <h2 class="text-[16px] font-bold text-gray-900">Edit Member</h2>
                                <p class="mt-0.5 text-[12.5px] text-gray-400">Perubahan akan langsung tersimpan</p>
                            </div>
                            <button @click="showEditModal = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                <X class="size-4" />
                            </button>
                        </div>

                        <!-- Form -->
                        <form class="space-y-4 px-6 py-5" @submit.prevent="submitEdit">

                            <!-- Nama Lengkap -->
                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                    :class="{ 'border-red-300': editForm.errors.name }"
                                />
                                <InputError :message="editForm.errors.name" class="mt-1" />
                            </div>

                            <!-- Username | Role -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Username <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="editForm.username"
                                        type="text"
                                        class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                        :class="{ 'border-red-300': editForm.errors.username }"
                                    />
                                    <InputError :message="editForm.errors.username" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Role <span class="text-red-500">*</span></label>
                                    <TomSelectInput v-model="editForm.role_id" :options="roleOptions" placeholder="Pilih role..." />
                                    <InputError :message="editForm.errors.role_id" class="mt-1" />
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                    :class="{ 'border-red-300': editForm.errors.email }"
                                />
                                <InputError :message="editForm.errors.email" class="mt-1" />
                            </div>

                            <!-- Bidang | Status -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Bidang <span class="text-red-500">*</span></label>
                                    <TomSelectInput v-model="editForm.job_id" :options="jobTitleOptions" placeholder="Pilih bidang..." />
                                    <InputError :message="editForm.errors.job_id" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <TomSelectInput v-model="editForm.status_id" :options="statusOptions" placeholder="Pilih status..." />
                                    <InputError :message="editForm.errors.status_id" class="mt-1" />
                                </div>
                            </div>

                            <!-- Nomor HP -->
                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Nomor HP</label>
                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    placeholder="+62 812-0001-0001"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                />
                                <InputError :message="editForm.errors.phone" class="mt-1" />
                            </div>
                        </form>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 px-6 py-4">
                            <button type="button" @click="showEditModal = false" class="h-9 rounded-lg border border-gray-200 bg-white px-4 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50">
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="submitEdit"
                                :disabled="editForm.processing"
                                class="inline-flex h-9 items-center gap-2 rounded-lg bg-blue-600 px-4 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                            >
                                <span v-if="editForm.processing" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                Simpan
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Delete Confirm Modal ──────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteConfirm && deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showDeleteConfirm = false">
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                    <div v-if="showDeleteConfirm" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">

                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                                <Trash2 class="size-5 text-red-600" />
                            </div>
                            <div>
                                <h3 class="text-[15px] font-bold text-gray-900">Hapus Member?</h3>
                                <p class="mt-1 text-[13px] text-gray-500">
                                    <strong class="text-gray-700">{{ deleteTarget.name }}</strong> akan dihapus permanen dari sistem. Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-2.5">
                            <button @click="showDeleteConfirm = false" class="h-9 rounded-lg border border-gray-200 bg-white px-4 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50">
                                Cancel
                            </button>
                            <button
                                @click="executeDelete"
                                :disabled="deleting"
                                class="inline-flex h-9 items-center gap-2 rounded-lg bg-red-600 px-4 text-[13px] font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
                            >
                                <span v-if="deleting" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                Hapus
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Kelola Bidang Modal ──────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showBidangModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showBidangModal = false">
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                    <div v-if="showBidangModal" class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">

                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <div>
                                <h2 class="text-[16px] font-bold text-gray-900">Kelola Bidang</h2>
                                <p class="mt-0.5 text-[12.5px] text-gray-400">Tambah atau hapus bidang / job title</p>
                            </div>
                            <button @click="showBidangModal = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                <X class="size-4" />
                            </button>
                        </div>

                        <div class="max-h-56 overflow-y-auto px-6 pt-4">
                            <p class="mb-2 text-[11.5px] font-semibold uppercase tracking-wider text-gray-400">Bidang yang ada</p>
                            <ul class="space-y-1">
                                <li
                                    v-for="jt in jobTitles"
                                    :key="jt.id"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-[13px] text-gray-700 hover:bg-gray-50"
                                >
                                    <span>{{ jt.name }}</span>
                                    <button @click="deleteBidang(jt.id)" class="flex h-6 w-6 items-center justify-center rounded-md text-gray-300 transition hover:bg-red-50 hover:text-red-500">
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </li>
                                <li v-if="jobTitles.length === 0" class="py-3 text-center text-[13px] text-gray-400">
                                    Belum ada bidang.
                                </li>
                            </ul>
                        </div>

                        <form class="px-6 pb-5 pt-4" @submit.prevent="submitBidang">
                            <p class="mb-2 text-[11.5px] font-semibold uppercase tracking-wider text-gray-400">Tambah Bidang Baru</p>
                            <div class="flex gap-2">
                                <input
                                    v-model="bidangForm.name"
                                    type="text"
                                    placeholder="e.g. Mobile Developer"
                                    class="h-9 flex-1 rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                    :class="{ 'border-red-300': bidangForm.errors.name }"
                                />
                                <button
                                    type="submit"
                                    :disabled="bidangForm.processing || !bidangForm.name.trim()"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <Plus class="size-4" />
                                    Tambah
                                </button>
                            </div>
                            <InputError :message="bidangForm.errors.name" class="mt-1" />
                        </form>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Add Member Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showAddModal = false">
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                    <div v-if="showAddModal" class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                            <div>
                                <h2 class="text-[16px] font-bold text-gray-900">Add New Member</h2>
                                <p class="mt-0.5 text-[12.5px] text-gray-400">Member akan bisa login dengan password default</p>
                            </div>
                            <button @click="showAddModal = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                <X class="size-4" />
                            </button>
                        </div>

                        <form class="space-y-4 px-6 py-5" @submit.prevent="submitAdd">

                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input v-model="addForm.name" type="text" placeholder="e.g. Budi Santoso"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                    :class="{ 'border-red-300': addForm.errors.name }" />
                                <InputError :message="addForm.errors.name" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Username <span class="text-red-500">*</span></label>
                                    <input v-model="addForm.username" type="text" placeholder="budi_santoso"
                                        class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                        :class="{ 'border-red-300': addForm.errors.username }" />
                                    <InputError :message="addForm.errors.username" class="mt-1" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Role <span class="text-red-500">*</span></label>
                                    <TomSelectInput v-model="addForm.role_id" :options="roleOptions" placeholder="Pilih role..." />
                                    <InputError :message="addForm.errors.role_id" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                                <input v-model="addForm.email" type="email" placeholder="budi@company.co.id"
                                    class="h-9 w-full rounded-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                                    :class="{ 'border-red-300': addForm.errors.email }" />
                                <InputError :message="addForm.errors.email" class="mt-1" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Bidang / Job Title <span class="text-red-500">*</span></label>
                                <TomSelectInput v-model="addForm.job_id" :options="jobTitleOptions" placeholder="Pilih bidang..." />
                                <InputError :message="addForm.errors.job_id" class="mt-1" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[12.5px] font-semibold text-gray-700">Nomor HP</label>
                                <div class="flex">
                                    <span class="flex h-9 items-center rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 px-3 text-[13px] text-gray-500 select-none">+62</span>
                                    <input v-model="addForm.phone" type="text" placeholder="812-0001-0009"
                                        class="h-9 min-w-0 flex-1 rounded-r-lg border border-gray-200 px-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50" />
                                </div>
                                <InputError :message="addForm.errors.phone" class="mt-1" />
                            </div>

                            <div class="rounded-lg bg-blue-50 px-3.5 py-3 text-[12.5px] text-blue-700">
                                Password default: <strong>password</strong> — member dapat menggantinya setelah login pertama.
                            </div>
                        </form>

                        <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 px-6 py-4">
                            <button type="button" @click="showAddModal = false" class="h-9 rounded-lg border border-gray-200 bg-white px-4 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="button" @click="submitAdd" :disabled="addForm.processing"
                                class="inline-flex h-9 items-center gap-2 rounded-lg bg-blue-600 px-4 text-[13px] font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                            >
                                <span v-if="addForm.processing" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                Add Member
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
