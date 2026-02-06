<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl bg-white shadow-xl">
        <img src="/images/logo-black.png" />
    </div>
</template>
