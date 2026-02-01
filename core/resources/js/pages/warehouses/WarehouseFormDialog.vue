<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Warehouse } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    open: boolean | Warehouse;
    warehouse?: Warehouse | null;
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

const isEditing = computed(() => !!props.warehouse);

const form = useForm({
    code: props.warehouse?.code || '',
    name: props.warehouse?.name || '',
    address: props.warehouse?.address || '',
    is_active: props.warehouse?.is_active ?? true,
});

function submit() {
    if (isEditing.value && props.warehouse) {
        form.patch(`/warehouses/${props.warehouse.id}`, {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    } else {
        form.post('/warehouses', {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    }
}

watch(
    () => props.open,
    (value) => {
        if (value && props.warehouse) {
            form.code = props.warehouse.code;
            form.name = props.warehouse.name;
            form.address = props.warehouse.address || '';
            form.is_active = props.warehouse.is_active;
        } else if (!value) {
            form.reset();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Warehouse' : 'New Warehouse'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update warehouse information.'
                            : 'Add a new warehouse location.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="code">
                            Code
                            <span class="text-xs font-normal text-muted-foreground">
                                (auto-generated if empty)
                            </span>
                        </Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder="WH-0001"
                        />
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Main Warehouse"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Textarea
                            id="address"
                            v-model="form.address"
                            placeholder="Enter warehouse address..."
                            rows="3"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            :checked="form.is_active"
                            @update:checked="form.is_active = $event"
                        />
                        <Label for="is_active" class="cursor-pointer">
                            Active
                        </Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
