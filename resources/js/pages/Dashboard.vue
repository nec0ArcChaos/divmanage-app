<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

const form = useForm({});
const handleLogout = () => form.post(logout().url);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-gray-50">
        <p class="text-xl font-semibold text-gray-700">ini ceritanya dashboard</p>
        <Button
            variant="outline"
            :disabled="form.processing"
            @click="handleLogout"
        >
            <LogOut class="size-4" />
            Sign Out
        </Button>
    </div>
</template>
