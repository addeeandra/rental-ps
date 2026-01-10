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

interface Product {
    id: number;
    code: string;
    name: string;
}

interface Props {
    open: boolean | Product;
    product: Product;
}

const props = defineProps<Props>();
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
    if (!props.product) return;

    isDeleting.value = true;

    router.delete(`/products/${props.product.id}`, {
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
                <DialogTitle>Delete Product</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <strong class="text-foreground">{{ product.name }}</strong>
                    ({{ product.code }})?
                </DialogDescription>
            </DialogHeader>

            <div
                class="rounded-lg border border-destructive/50 bg-destructive/10 p-4"
            >
                <p class="text-sm text-destructive">
                    <strong>Warning:</strong> This action cannot be undone. All
                    product data will be permanently deleted.
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
                    {{ isDeleting ? 'Deleting...' : 'Delete Product' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
