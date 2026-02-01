<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { InventoryItem } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    open: boolean | InventoryItem;
    item: InventoryItem;
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

const form = useForm({});

function submit() {
    form.delete(`/inventory-items/${props.item.id}`, {
        onSuccess: () => {
            emit('success');
        },
    });
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>Delete Inventory Item</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <strong>{{ item.name }}</strong
                    >? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="isOpen = false">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
