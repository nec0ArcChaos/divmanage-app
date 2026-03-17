<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Code2,
    FlaskConical,
    MoreHorizontal,
    Plus,
    Search,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

// ── Types ──────────────────────────────────────────────────────────────────
type GlobalRole = 'admin' | 'project_manager' | 'developer' | 'qa';
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
    avatar: string | null;
    global_role: GlobalRole;
    status: MemberStatus;
    job_title: string | null;
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

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps<{
    members: TeamMember[];
    stats: Stats;
    activeProjects: ActiveProject[];
}>();

// ── Filters ────────────────────────────────────────────────────────────────
const searchQuery  = ref('');
const roleFilter   = ref<'all' | GlobalRole>('all');
const statusFilter = ref<'all' | MemberStatus>('all');

const filteredMembers = computed(() => {
    let result = [...props.members];
    if (roleFilter.value !== 'all') {
        result = result.filter(m => m.global_role === roleFilter.value);
    }
    if (statusFilter.value !== 'all') {
        result = result.filter(m => m.status === statusFilter.value);
    }
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
            <button
                disabled
                title="Coming soon"
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white opacity-60 shadow-sm"
            >
                <Plus class="size-4" />
                Add Member
            </button>
        </div>

        <!-- ── Stats Grid ───────────────────────────────────────────────── -->
        <div class="mb-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <!-- Total Members -->
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

            <!-- Developers -->
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

            <!-- QA Engineers -->
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

            <!-- Project Managers -->
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
            <!-- Search -->
            <div class="relative min-w-[200px] max-w-xs flex-1">
                <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search members..."
                    class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-[13px] text-gray-700 outline-none transition focus:border-blue-300 focus:ring-3 focus:ring-blue-50"
                />
            </div>

            <!-- Role filter -->
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

            <!-- Status filter -->
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
                                    <div
                                        :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-white', avatarColor(member.id)]"
                                    >
                                        {{ member.initials }}
                                    </div>
                                    <div>
                                        <p class="text-[13.5px] font-semibold text-gray-800">{{ member.name }}</p>
                                        <p class="mt-0.5 text-[12px] text-gray-400">@{{ member.username }}</p>
                                    </div>
                                </div>
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
                                    <button class="inline-flex h-7 items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 text-[12px] font-medium text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        View
                                    </button>
                                    <button class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                        <MoreHorizontal class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="filteredMembers.length === 0">
                            <td colspan="6" class="py-12 text-center text-[13px] text-gray-400">
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
                    <span
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{ background: project.color }"
                    />
                    <div>
                        <p class="text-[14px] font-semibold text-gray-800">{{ project.name }}</p>
                        <p class="mt-0.5 text-[12px] text-gray-400">{{ project.members.length }} member{{ project.members.length !== 1 ? 's' : '' }}</p>
                    </div>
                </div>

                <!-- Avatar stack -->
                <div class="flex items-center">
                    <div
                        v-for="(member, idx) in project.members.slice(0, 4)"
                        :key="idx"
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white',
                            idx > 0 ? '-ml-2' : '',
                            avatarColor(idx),
                        ]"
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
</template>
