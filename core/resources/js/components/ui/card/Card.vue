<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-xl border border-gray-100 py-6 shadow-lg shadow-gray-100 dark:border-gray-800 dark:shadow-none',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
