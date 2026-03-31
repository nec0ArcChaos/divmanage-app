<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import { Bell, CheckCheck, MessageSquare } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';

interface NotificationData {
    task_id: number;
    task_title: string;
    project_id: number;
    project_name: string;
    commenter_name: string;
    comment_preview: string;
}

interface NotificationItem {
    id: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
}

interface NotificationsShared {
    unread_count: number;
    recent: NotificationItem[];
}

const page = usePage<{ notifications: NotificationsShared }>();

const open     = ref(false);
const items    = ref<NotificationItem[]>([]);
const unread   = ref(0);
const dropdown = ref<HTMLDivElement | null>(null);
const currentUserId = (page.props.auth as any)?.user?.id as number | null;

// Sync from Inertia shared data on mount / page visits
function syncFromPage() {
    const n = page.props.notifications as NotificationsShared | undefined;
    if (n) {
        items.value  = n.recent ?? [];
        unread.value = n.unread_count ?? 0;
    }
}

let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    syncFromPage();
    document.addEventListener('click', onClickOutside);

    // Subscribe to real-time notifications for this user
    if (currentUserId) {
        window.Echo.private(`user.${currentUserId}`).listen(
            '.notification.created',
            (event: { notification: NotificationItem }) => {
                // Prepend so newest appears first
                items.value.unshift(event.notification);
                unread.value++;
            },
        );
    }

    // Poll every 30 seconds as a fallback to keep state in sync
    pollInterval = setInterval(async () => {
        try {
            const { data } = await axios.get<NotificationsShared>('/notifications/counts');
            items.value  = data.recent ?? [];
            unread.value = data.unread_count ?? 0;
        } catch {
            // silently ignore polling errors
        }
    }, 30_000);
});

onUnmounted(() => {
    document.removeEventListener('click', onClickOutside);
    if (pollInterval) clearInterval(pollInterval);
    if (currentUserId) window.Echo.leave(`user.${currentUserId}`);
});

function onClickOutside(e: MouseEvent) {
    if (dropdown.value && !dropdown.value.contains(e.target as Node)) {
        open.value = false;
    }
}

async function markRead(item: NotificationItem) {
    const taskId = item.data.task_id;

    // Optimistically mark ALL notifications from this task as read
    const now = new Date().toISOString();
    let freed = 0;
    items.value.forEach((n) => {
        if (n.data.task_id === taskId && !n.read_at) {
            n.read_at = now;
            freed++;
        }
    });
    unread.value = Math.max(0, unread.value - freed);

    open.value = false;
    router.visit(`/projects?open_task=${taskId}&project_id=${item.data.project_id}`);

    // Fire-and-forget — navigation already happened, no need to await
    axios.post(`/notifications/read-by-task/${taskId}`).catch(() => {});
}

async function readAll() {
    try {
        await axios.post('/notifications/read-all');
        items.value.forEach((n) => {
            if (!n.read_at) n.read_at = new Date().toISOString();
        });
        unread.value = 0;
    } catch {
        // silently ignore
    }
}

const hasUnread = computed(() => unread.value > 0);
</script>

<template>
    <div ref="dropdown" class="relative">
        <!-- Bell button -->
        <button
            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
            :title="unread > 0 ? `${unread} unread notifications` : 'Notifications'"
            @click="open = !open"
        >
            <Bell class="size-4.5" />
            <!-- Unread badge -->
            <span
                v-if="hasUnread"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white"
            >{{ unread > 99 ? '99+' : unread }}</span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition-all duration-150"
            enter-from-class="opacity-0 -translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-100"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-1 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 top-10 z-50 w-[340px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <span class="text-[13px] font-semibold text-gray-800">
                        Notifications
                        <span v-if="hasUnread" class="ml-1 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">
                            {{ unread }} baru
                        </span>
                    </span>
                    <button
                        v-if="hasUnread"
                        class="flex items-center gap-1 text-[11.5px] font-medium text-blue-600 transition hover:text-blue-800"
                        @click="readAll"
                    >
                        <CheckCheck class="size-3.5" />
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-[380px] overflow-y-auto">
                    <!-- Empty -->
                    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                        <Bell class="mb-2 size-6 text-gray-300" />
                        <p class="text-[12.5px] text-gray-400">Tidak ada notifikasi</p>
                    </div>

                    <button
                        v-for="item in items"
                        :key="item.id"
                        :class="[
                            'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-gray-50',
                            !item.read_at ? 'bg-blue-50/50' : ''
                        ]"
                        @click="markRead(item)"
                    >
                        <!-- Icon -->
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100">
                            <MessageSquare class="size-3.5 text-blue-600" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] font-medium text-gray-800 leading-snug">
                                <span class="font-semibold">{{ item.data.commenter_name }}</span>
                                berkomentar di
                                <span class="font-semibold">{{ item.data.task_title }}</span>
                            </p>
                            <p
                                v-if="item.data.comment_preview"
                                class="mt-0.5 line-clamp-2 text-[11.5px] text-gray-500"
                            >{{ item.data.comment_preview }}</p>
                            <p class="mt-1 text-[11px] text-gray-400">
                                {{ item.data.project_name }} · {{ item.created_at }}
                            </p>
                        </div>

                        <!-- Unread dot -->
                        <div v-if="!item.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
