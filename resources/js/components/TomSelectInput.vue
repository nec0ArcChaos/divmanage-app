<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

interface Option {
    value: number | string;
    label: string;
}

const props = defineProps<{
    options: Option[];
    placeholder?: string;
    disabled?: boolean;
}>();

const model = defineModel<number | string | ''>({ required: true });

const selectRef = ref<HTMLSelectElement | null>(null);
let ts: TomSelect | null = null;

onMounted(() => {
    if (!selectRef.value) return;

    ts = new TomSelect(selectRef.value, {
        allowEmptyOption: true,
        searchField: ['text'],
        maxOptions: 100,
        onChange(value: string) {
            const num = Number(value);
            model.value = value === '' ? '' : (isNaN(num) ? value : num);
        },
    });

    // Sync initial value (silent = no onChange fired)
    if (model.value !== '') {
        ts.setValue(String(model.value), true);
    }

    if (props.disabled) {
        ts.disable();
    }
});

watch(
    () => model.value,
    (val) => {
        if (!ts) return;
        const current = ts.getValue() as string;
        const next = val === '' ? '' : String(val);
        if (current !== next) {
            ts.setValue(next, true);
        }
    },
);

watch(
    () => props.disabled,
    (disabled) => {
        disabled ? ts?.disable() : ts?.enable();
    },
);

onUnmounted(() => {
    ts?.destroy();
    ts = null;
});
</script>

<template>
    <div :class="['tom-select-wrapper', { 'ts-wrapper-disabled': disabled }]">
        <select ref="selectRef" style="display:none">
            <option value="">{{ placeholder ?? 'Select...' }}</option>
            <option
                v-for="opt in options"
                :key="opt.value"
                :value="opt.value"
                :selected="String(opt.value) === String(model)"
            >
                {{ opt.label }}
            </option>
        </select>
    </div>
</template>

<style>
/* Override Tom Select styles to match app's design system */
.ts-wrapper {
    width: 100%;
}

.ts-control {
    height: 36px !important;
    min-height: 36px !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    padding: 0 12px !important;
    font-size: 13px !important;
    font-family: inherit !important;
    color: #374151 !important;
    background: #fff !important;
    box-shadow: none !important;
    display: flex !important;
    align-items: center !important;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.ts-control input {
    font-size: 13px !important;
    color: #374151 !important;
    font-family: inherit !important;
    line-height: 1 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.ts-control:focus-within,
.ts-wrapper.focus .ts-control {
    border-color: #93c5fd !important;
    box-shadow: 0 0 0 3px rgba(219, 234, 254, 0.8) !important;
    outline: none !important;
}

.ts-dropdown {
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.1) !important;
    font-size: 13px !important;
    font-family: inherit !important;
    margin-top: 4px !important;
    overflow: hidden !important;
}

.ts-dropdown .option {
    padding: 8px 12px !important;
    color: #374151 !important;
    cursor: pointer;
}

.ts-dropdown .option:hover,
.ts-dropdown .option.active {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
}

.ts-dropdown .option.selected {
    background: #dbeafe !important;
    color: #1e40af !important;
    font-weight: 600 !important;
}

/* Disabled state */
.ts-wrapper-disabled .ts-control,
.ts-wrapper.disabled .ts-control {
    background: #f9fafb !important;
    border-color: #e5e7eb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.7 !important;
    pointer-events: none !important;
}
</style>
