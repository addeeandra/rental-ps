<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Partner {
    id: number;
    code: string;
    name: string;
}

interface Props {
    open: boolean | Partner;
    partner: Partner;
    deleteRoute?: string;
}

const props = withDefaults(defineProps<Props>(), {
    deleteRoute: 'partners.destroy',
});
const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
    cancel: [];
}>();

const isOpen = computed({
    get: () => !!props.open,
    set: (value) => {
        if (!value) {
            emit('update:open', false);
            emit('cancel');
        }
    },
});

const isDeleting = ref(false);

function handleDelete() {
    if (!props.partner) return;

    isDeleting.value = true;

    router.delete(route(props.deleteRoute, props.partner.id), {
        onSuccess: () => {
            emit('success');
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Delete Partner</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <strong class="text-foreground">{{ partner.name }}</strong>
                    ({{ partner.code }})?
                </DialogDescription>
            </DialogHeader>

            <div
                class="rounded-lg border border-destructive/50 bg-destructive/10 p-4"
            >
                <p class="text-sm text-destructive">
                    <strong>Warning:</strong> This action cannot be undone. All
                    partner data will be permanently deleted.
                </p>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline" :disabled="isDeleting">
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    @click="handleDelete"
                    :disabled="isDeleting"
                >
                    {{ isDeleting ? 'Deleting...' : 'Delete Partner' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
