<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import type { Invoice } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    open: boolean | Invoice;
    invoice: Invoice;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
    cancel: [];
}>();

const isOpen = computed({
    get: () => Boolean(props.open),
    set: (value) => emit('update:open', value),
});

const form = useForm({});

function handleDelete() {
    form.delete(`/invoices/${props.invoice.id}`, {
        onSuccess: () => {
            emit('success');
        },
    });
}
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete Invoice</AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to delete invoice
                    <strong>{{ invoice.invoice_number }}</strong
                    >? This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="emit('cancel')"
                    >Cancel</AlertDialogCancel
                >
                <AlertDialogAction
                    @click="handleDelete"
                    class="bg-destructive hover:bg-destructive/90"
                    :disabled="form.processing"
                >
                    Delete
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
