<script setup lang="ts">
import { ref } from 'vue';
import DashboardSidebar from './partials/DashboardSidebar.vue';
import DashboardTopbar from './partials/DashboardTopbar.vue';

const isSidebarOpen = ref(false);
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50">
        <DashboardSidebar
            :is-open="isSidebarOpen"
            @close="isSidebarOpen = false"
        />
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <DashboardTopbar @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
