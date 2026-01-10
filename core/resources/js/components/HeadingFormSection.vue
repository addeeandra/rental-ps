<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header class="dark: border-b border-gray-200 dark:border-gray-800/50">
        <h3 class="mb-0.5 text-sm font-medium tracking-wide">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
