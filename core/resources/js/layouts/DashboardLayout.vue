<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    activeTab: 'overview' | 'financial' | 'operations';
}

const props = defineProps<Props>();

function handleTabChange(value: string) {
    const routes = {
        overview: '/dashboard',
        financial: '/dashboard/financial',
        operations: '/dashboard/operations',
    };

    router.visit(routes[value as keyof typeof routes], {
        only: ['activeTab', 'tabData', 'hasData'],
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground">
                    Overview of your business performance and operations
                </p>
            </div>

            <!-- Tabs Navigation -->
            <Tabs
                :model-value="activeTab"
                @update:model-value="handleTabChange"
            >
                <TabsList
                    class="grid w-full grid-cols-3 md:w-auto md:grid-cols-3"
                >
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="financial">Financial</TabsTrigger>
                    <TabsTrigger value="operations">Operations</TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- Tab Content Slot -->
            <slot />
        </div>
    </AppLayout>
</template>
