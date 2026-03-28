<script setup lang="ts">
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Code,
    Italic,
    List,
    ListOrdered,
    Quote,
} from 'lucide-vue-next';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = new Editor({
    content: props.modelValue,
    extensions: [StarterKit],
    editable: !props.disabled,
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none min-h-[80px] px-3 py-2.5 text-[13.5px] text-gray-900 outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        // Treat empty paragraph as empty string
        emit('update:modelValue', html === '<p></p>' ? '' : html);
    },
});

watch(
    () => props.modelValue,
    (val) => {
        const current = editor.getHTML();
        const normalized = current === '<p></p>' ? '' : current;
        if (val !== normalized) {
            editor.commands.setContent(val || '', false);
        }
    },
);

watch(
    () => props.disabled,
    (val) => {
        editor.setEditable(!val);
    },
);

onBeforeUnmount(() => {
    editor.destroy();
});
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white transition focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100">
        <!-- Toolbar -->
        <div class="flex items-center gap-0.5 border-b border-gray-100 px-2 py-1.5">
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('bold') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            ><Bold class="size-3.5" /></button>
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('italic') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            ><Italic class="size-3.5" /></button>
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('code') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Inline code"
                @click="editor.chain().focus().toggleCode().run()"
            ><Code class="size-3.5" /></button>
            <span class="mx-1 h-4 w-px bg-gray-200" />
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('bulletList') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            ><List class="size-3.5" /></button>
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('orderedList') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Ordered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            ><ListOrdered class="size-3.5" /></button>
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('blockquote') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Blockquote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            ><Quote class="size-3.5" /></button>
            <button
                type="button"
                :class="['rounded p-1 transition hover:bg-gray-100', editor.isActive('codeBlock') ? 'bg-gray-100 text-blue-600' : 'text-gray-500']"
                title="Code block"
                @click="editor.chain().focus().toggleCodeBlock().run()"
            ><span class="text-[10px] font-mono font-bold text-gray-500">{ }</span></button>
        </div>
        <!-- Editor area -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style scoped>
/* Prose styles for editor content */
:deep(.prose) {
    font-size: 13.5px;
    color: #111827;
}
:deep(.prose p) { margin: 0 0 0.4em; }
:deep(.prose p:last-child) { margin-bottom: 0; }
:deep(.prose strong) { font-weight: 600; }
:deep(.prose em) { font-style: italic; }
:deep(.prose code) {
    background: #f3f4f6;
    border-radius: 3px;
    padding: 1px 4px;
    font-size: 12px;
    font-family: ui-monospace, monospace;
}
:deep(.prose pre) {
    background: #1e1e2e;
    color: #cdd6f4;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 12px;
    overflow-x: auto;
    margin: 0.5em 0;
}
:deep(.prose pre code) {
    background: transparent;
    padding: 0;
    color: inherit;
}
:deep(.prose blockquote) {
    border-left: 3px solid #d1d5db;
    padding-left: 12px;
    color: #6b7280;
    margin: 0.5em 0;
}
:deep(.prose ul) { padding-left: 1.25em; list-style-type: disc; margin: 0.3em 0; }
:deep(.prose ol) { padding-left: 1.25em; list-style-type: decimal; margin: 0.3em 0; }
:deep(.prose li) { margin: 0.1em 0; }
/* Placeholder */
:deep(.tiptap p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
