<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header class="border-b border-gray-200">
        <h3 class="mb-0.5 text-sm font-medium tracking-wide">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
