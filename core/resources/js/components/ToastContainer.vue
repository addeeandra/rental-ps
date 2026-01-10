<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/composables/useToast';
import { AlertCircle, CheckCircle2, X } from 'lucide-vue-next';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 z-50 flex flex-col items-end justify-end gap-2 p-4 md:p-6"
    >
        <TransitionGroup
            enter-active-class="transition-all duration-300"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <Alert
                v-for="toast in toasts"
                :key="toast.id"
                :variant="toast.type === 'error' ? 'destructive' : 'default'"
                class="pointer-events-auto w-full max-w-md shadow-lg"
            >
                <CheckCircle2
                    v-if="toast.type === 'success'"
                    class="h-4 w-4 text-green-600"
                />
                <AlertCircle
                    v-else-if="toast.type === 'error'"
                    class="h-4 w-4"
                />
                <AlertCircle v-else class="h-4 w-4" />

                <AlertDescription class="flex items-center justify-between">
                    <span>{{ toast.message }}</span>
                    <button
                        @click="removeToast(toast.id)"
                        class="ml-4 rounded-sm opacity-70 transition-opacity hover:opacity-100"
                    >
                        <X class="h-4 w-4" />
                        <span class="sr-only">Close</span>
                    </button>
                </AlertDescription>
            </Alert>
        </TransitionGroup>
    </div>
</template>
