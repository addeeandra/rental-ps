<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-12 items-center justify-center overflow-visible pb-2"
    >
        <AppLogoIcon class="size-8 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">XGame</span>
        <span
            class="mb-0.5 truncate text-xs leading-tight font-semibold text-gray-500"
            >Playstation</span
        >
    </div>
</template>
