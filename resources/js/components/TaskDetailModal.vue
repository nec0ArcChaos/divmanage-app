<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types/auth';
import {
    AlertCircle,
    Calendar,
    Download,
    FileText,
    Loader2,
    MessageSquare,
    Paperclip,
    Send,
    Trash2,
    X,
} from 'lucide-vue-next';
import TiptapEditor from '@/components/TiptapEditor.vue';

// ── Types ────────────────────────────────────────────────────────────────────

interface TaskAttachment {
    id: number;
    original_name: string;
    mime_type: string | null;
    size: number;
    size_human: string;
    download_url: string;
    is_image: boolean;
}

interface LocalComment {
    id: number | string;
    body: string;
    created_at: string;
    created_at_iso: string;
    is_mine: boolean;
    sending: boolean;
    user: { id: number; name: string; initials: string };
    attachments: TaskAttachment[];
}

interface TaskProp {
    id: number;
    title: string;
    priority: string;
    deadline: string | null;
    assignorName: string;
    assignedAt: string | null;
    comment_count: number;
    status: { name: string; color: string; is_done: boolean } | null;
}

// ── Props / Emits ─────────────────────────────────────────────────────────────

const props = defineProps<{
    modelValue: boolean;
    task: TaskProp | null;
    projectName?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [val: boolean];
    'comment-added': [];
}>();

// ── State ─────────────────────────────────────────────────────────────────────

const page         = usePage<{ auth: Auth }>();
const comments     = ref<LocalComment[]>([]);
const loading      = ref(false);
const submitting   = ref(false);
const error        = ref('');
const body         = ref('');
const pendingFiles = ref<File[]>([]);
const fileInput    = ref<HTMLInputElement | null>(null);
const commentEnd   = ref<HTMLDivElement | null>(null);

// ── Echo channel management ───────────────────────────────────────────────────

let subscribedTaskId: number | null = null;

function subscribeToTask(taskId: number) {
    unsubscribeFromTask();
    subscribedTaskId = taskId;
    window.Echo.private(`task.${taskId}`).listen(
        '.comment.created',
        (event: { comment: LocalComment }) => {
            if (event.comment.user.id === page.props.auth.user.id) return;
            addCommentIfNotPresent(event.comment);
        },
    );
}

function unsubscribeFromTask() {
    if (subscribedTaskId !== null) {
        window.Echo.leave(`task.${subscribedTaskId}`);
        subscribedTaskId = null;
    }
}

function addCommentIfNotPresent(incoming: LocalComment) {
    const already = comments.value.some(
        (c) => typeof c.id === 'number' && c.id === incoming.id,
    );
    if (!already) {
        comments.value.push({ ...incoming, sending: false });
        nextTick(() => commentEnd.value?.scrollIntoView({ behavior: 'smooth' }));
    }
}

// ── Watchers ──────────────────────────────────────────────────────────────────

watch(
    () => props.modelValue,
    async (open) => {
        if (open && props.task) {
            comments.value = [];
            body.value = '';
            pendingFiles.value = [];
            error.value = '';
            await loadComments();
            subscribeToTask(props.task.id);
        } else {
            unsubscribeFromTask();
        }
    },
);

// ── Helpers ───────────────────────────────────────────────────────────────────

function close() {
    emit('update:modelValue', false);
}

function sanitize(html: string): string {
    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li', 's'],
        ALLOWED_ATTR: [],
    });
}

function getInitials(name: string): string {
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

async function loadComments() {
    if (!props.task) return;
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get<LocalComment[]>(`/tasks/${props.task.id}/comments`);
        comments.value = data.map((c) => ({ ...c, sending: false }));
        await nextTick();
        commentEnd.value?.scrollIntoView({ behavior: 'smooth' });
    } catch {
        error.value = 'Failed to load comments.';
    } finally {
        loading.value = false;
    }
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    const newFiles = Array.from(input.files);
    // Max 5 total
    const remaining = 5 - pendingFiles.value.length;
    pendingFiles.value.push(...newFiles.slice(0, remaining));
    input.value = '';
}

function removeFile(index: number) {
    pendingFiles.value.splice(index, 1);
}

async function submitComment() {
    if (!props.task || !body.value.trim()) return;

    error.value = '';

    // ── Optimistic UI: show comment immediately ──
    const savedBody  = body.value;
    const savedFiles = [...pendingFiles.value];
    const tempId     = `opt-${Date.now()}`;
    const currentUser = page.props.auth.user;

    const optimisticComment: LocalComment = {
        id:             tempId,
        body:           savedBody,
        created_at:     'Just now',
        created_at_iso: new Date().toISOString(),
        is_mine:        true,
        sending:        true,
        user:           { id: currentUser.id, name: currentUser.name, initials: getInitials(currentUser.name) },
        attachments:    [],
    };

    comments.value.push(optimisticComment);
    body.value = '';
    pendingFiles.value = [];
    emit('comment-added');
    await nextTick();
    commentEnd.value?.scrollIntoView({ behavior: 'smooth' });

    // ── Send to server ──
    submitting.value = true;
    const formData = new FormData();
    formData.append('body', savedBody);
    savedFiles.forEach((f, i) => formData.append(`attachments[${i}]`, f));

    const headers: Record<string, string> = { 'Content-Type': 'multipart/form-data' };
    const socketId = window.Echo?.socketId?.() ?? null;
    if (socketId) headers['X-Socket-ID'] = socketId;

    try {
        const { data } = await axios.post<LocalComment>(
            `/tasks/${props.task.id}/comments`,
            formData,
            { headers },
        );

        // Replace optimistic comment with real server data
        const index = comments.value.findIndex((c) => c.id === tempId);
        if (index !== -1) {
            comments.value[index] = { ...data, sending: false };
        }
    } catch (err: any) {
        // Rollback: remove optimistic comment, restore form
        comments.value = comments.value.filter((c) => c.id !== tempId);
        body.value     = savedBody;
        pendingFiles.value = savedFiles;
        error.value    = err?.response?.data?.message ?? 'Failed to post comment.';
    } finally {
        submitting.value = false;
    }
}

async function deleteComment(comment: LocalComment) {
    if (comment.sending) return;
    if (!confirm('Delete this comment?')) return;
    try {
        await axios.delete(`/task-comments/${comment.id}`);
        comments.value = comments.value.filter((c) => c.id !== comment.id);
    } catch {
        alert('Failed to delete comment.');
    }
}

function priorityClass(priority: string): string {
    return {
        low: 'bg-gray-100 text-gray-600',
        medium: 'bg-yellow-50 text-yellow-700',
        high: 'bg-orange-50 text-orange-700',
        critical: 'bg-red-50 text-red-700',
    }[priority] ?? 'bg-gray-100 text-gray-500';
}

function fileIcon(mime: string | null): boolean {
    return (mime ?? '').startsWith('image/');
}
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[60] bg-black/50"
                @click.self="close"
            />
        </Transition>

        <!-- Panel -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="modelValue && task"
                class="fixed inset-0 z-[70] flex items-center justify-center p-4"
                style="pointer-events: none;"
            >
                <div
                    class="flex h-full max-h-[720px] w-full max-w-[640px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                    style="pointer-events: auto;"
                >
                    <!-- ── Header ─────────────────────────────────────────── -->
                    <div class="flex shrink-0 items-start justify-between border-b border-gray-100 px-6 py-4">
                        <div class="min-w-0 flex-1 pr-4">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <!-- Status badge -->
                                <span
                                    v-if="task.status"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :style="{ backgroundColor: task.status.color + '20', color: task.status.color }"
                                >
                                    <span class="mr-1 h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: task.status.color }" />
                                    {{ task.status.name }}
                                </span>
                                <!-- Priority badge -->
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                                    :class="priorityClass(task.priority)"
                                >{{ task.priority }}</span>
                            </div>
                            <h2 class="text-[15px] font-semibold text-gray-900 leading-snug">{{ task.title }}</h2>
                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-gray-400">
                                <span v-if="projectName">
                                    <span class="font-medium text-gray-500">Project:</span> {{ projectName }}
                                </span>
                                <span v-if="task.deadline">
                                    <Calendar class="mr-0.5 inline size-3" />{{ task.deadline }}
                                </span>
                                <span>
                                    Assigned by <span class="font-medium text-gray-500">{{ task.assignorName }}</span>
                                    <template v-if="task.assignedAt"> · {{ task.assignedAt }}</template>
                                </span>
                            </div>
                        </div>
                        <button
                            class="shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                            @click="close"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <!-- ── Comments list ──────────────────────────────────── -->
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <!-- Loading state -->
                        <div v-if="loading" class="flex items-center justify-center py-10">
                            <Loader2 class="size-5 animate-spin text-gray-400" />
                        </div>

                        <!-- Error state -->
                        <div v-else-if="error && comments.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                            <AlertCircle class="mb-2 size-6 text-red-400" />
                            <p class="text-[13px] text-red-500">{{ error }}</p>
                        </div>

                        <!-- Empty state -->
                        <div v-else-if="comments.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 py-10 text-center">
                            <MessageSquare class="mb-3 size-7 text-gray-300" />
                            <p class="text-[13px] font-medium text-gray-500">No comments yet</p>
                            <p class="mt-0.5 text-[12px] text-gray-400">Be the first to comment on this task.</p>
                        </div>

                        <!-- Comment list -->
                        <div v-else class="space-y-5">
                            <div
                                v-for="comment in comments"
                                :key="comment.id"
                                class="flex gap-3 transition-opacity"
                                :class="{ 'opacity-60': comment.sending }"
                            >
                                <!-- Avatar -->
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white mt-0.5">
                                    {{ comment.user.initials }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[12.5px] font-semibold text-gray-800">{{ comment.user.name }}</span>
                                        <span class="text-[11px] text-gray-400">
                                            {{ comment.sending ? 'Sending…' : comment.created_at }}
                                        </span>
                                        <button
                                            v-if="comment.is_mine && !comment.sending"
                                            class="ml-auto rounded p-0.5 text-gray-300 transition hover:text-red-400"
                                            title="Delete comment"
                                            @click="deleteComment(comment)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>

                                    <!-- Comment body (sanitized HTML) -->
                                    <div
                                        class="comment-body rounded-lg border border-gray-100 bg-gray-50 px-3.5 py-2.5 text-[13px] text-gray-700"
                                        v-html="sanitize(comment.body)"
                                    />

                                    <!-- Attachments -->
                                    <div v-if="comment.attachments.length > 0" class="mt-2 flex flex-wrap gap-2">
                                        <a
                                            v-for="att in comment.attachments"
                                            :key="att.id"
                                            :href="att.download_url"
                                            target="_blank"
                                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-[11.5px] text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                        >
                                            <FileText v-if="!att.is_image" class="size-3.5 shrink-0 text-gray-400" />
                                            <span class="max-w-[140px] truncate">{{ att.original_name }}</span>
                                            <span class="shrink-0 text-gray-400">· {{ att.size_human }}</span>
                                            <Download class="size-3 shrink-0 text-gray-400" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div ref="commentEnd" />
                    </div>

                    <!-- ── Comment input ──────────────────────────────────── -->
                    <div class="shrink-0 border-t border-gray-100 px-6 py-4">
                        <!-- Error banner -->
                        <div v-if="error && comments.length > 0" class="mb-3 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-[12px] text-red-600">
                            <AlertCircle class="size-4 shrink-0" />
                            {{ error }}
                        </div>

                        <!-- Tiptap Editor -->
                        <TiptapEditor
                            v-model="body"
                            :placeholder="'Write a comment…'"
                            :disabled="submitting"
                        />

                        <!-- Pending file list -->
                        <div v-if="pendingFiles.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="(f, i) in pendingFiles"
                                :key="i"
                                class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 pl-2.5 pr-1.5 py-1 text-[11.5px] text-gray-600"
                            >
                                <Paperclip class="size-3 text-gray-400" />
                                <span class="max-w-[100px] truncate">{{ f.name }}</span>
                                <button type="button" class="rounded-full p-0.5 hover:text-red-500" @click="removeFile(i)">
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>

                        <!-- Actions row -->
                        <div class="mt-3 flex items-center justify-between">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-500 transition hover:bg-gray-50 disabled:opacity-50"
                                :disabled="pendingFiles.length >= 5 || submitting"
                                @click="fileInput?.click()"
                            >
                                <Paperclip class="size-3.5" />
                                Attach
                                <span v-if="pendingFiles.length > 0" class="text-blue-600">({{ pendingFiles.length }})</span>
                            </button>

                            <button
                                type="button"
                                :disabled="!body.trim() || submitting"
                                class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-1.5 text-[12.5px] font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="submitComment"
                            >
                                <Loader2 v-if="submitting" class="size-3.5 animate-spin" />
                                <Send v-else class="size-3.5" />
                                {{ submitting ? 'Sending…' : 'Send' }}
                            </button>
                        </div>

                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            accept="*/*"
                            class="hidden"
                            @change="onFileChange"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Prose styles for rendered comment HTML */
:deep(.comment-body p) { margin: 0 0 0.35em; }
:deep(.comment-body p:last-child) { margin-bottom: 0; }
:deep(.comment-body strong) { font-weight: 600; }
:deep(.comment-body em) { font-style: italic; }
:deep(.comment-body code) {
    background: #f3f4f6;
    border-radius: 3px;
    padding: 1px 4px;
    font-size: 11.5px;
    font-family: ui-monospace, monospace;
}
:deep(.comment-body pre) {
    background: #1e1e2e;
    color: #cdd6f4;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 12px;
    overflow-x: auto;
    margin: 0.4em 0;
}
:deep(.comment-body pre code) {
    background: transparent;
    padding: 0;
    color: inherit;
    font-size: inherit;
}
:deep(.comment-body blockquote) {
    border-left: 3px solid #d1d5db;
    padding-left: 10px;
    color: #6b7280;
    margin: 0.4em 0;
}
:deep(.comment-body ul) { padding-left: 1.25em; list-style-type: disc; margin: 0.3em 0; }
:deep(.comment-body ol) { padding-left: 1.25em; list-style-type: decimal; margin: 0.3em 0; }
:deep(.comment-body li) { margin: 0.1em 0; }
</style>
