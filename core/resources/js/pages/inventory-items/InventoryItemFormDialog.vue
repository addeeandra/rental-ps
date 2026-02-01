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
import type { InventoryItem, Partner } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    open: boolean | InventoryItem;
    item?: InventoryItem | null;
    suppliers: Partner[];
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

const isEditing = computed(() => !!props.item);

const form = useForm({
    sku: props.item?.sku || '',
    name: props.item?.name || '',
    owner_id: props.item?.owner_id?.toString() || '',
    unit: props.item?.unit || '',
    cost: props.item?.cost?.toString() || '0',
    default_share_percent: props.item?.default_share_percent?.toString() || '0',
    is_active: props.item?.is_active ?? true,
});

function submit() {
    if (isEditing.value && props.item) {
        form.patch(`/inventory-items/${props.item.id}`, {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    } else {
        form.post('/inventory-items', {
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
        if (value && props.item) {
            form.sku = props.item.sku;
            form.name = props.item.name;
            form.owner_id = props.item.owner_id?.toString() || '';
            form.unit = props.item.unit || '';
            form.cost = props.item.cost?.toString() || '0';
            form.default_share_percent = props.item.default_share_percent?.toString() || '0';
            form.is_active = props.item.is_active;
        } else if (!value) {
            form.reset();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[550px]">
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Inventory Item' : 'New Inventory Item'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update inventory item information.'
                            : 'Add a new inventory item with owner assignment.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="sku">
                                SKU
                                <span class="text-xs font-normal text-muted-foreground">
                                    (auto-generated if empty)
                                </span>
                            </Label>
                            <Input
                                id="sku"
                                v-model="form.sku"
                                placeholder="INV-0001"
                            />
                            <InputError :message="form.errors.sku" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit">Unit</Label>
                            <Input
                                id="unit"
                                v-model="form.unit"
                                placeholder="pcs, kg, box..."
                            />
                            <InputError :message="form.errors.unit" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Item name"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="owner_id">Owner (Supplier) *</Label>
                        <select
                            id="owner_id"
                            v-model="form.owner_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Select owner...</option>
                            <option
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                :value="supplier.id"
                            >
                                {{ supplier.code }} - {{ supplier.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.owner_id" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="cost">Cost *</Label>
                            <Input
                                id="cost"
                                v-model="form.cost"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                                required
                            />
                            <InputError :message="form.errors.cost" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="default_share_percent">
                                Default Share %
                                <span class="text-xs font-normal text-muted-foreground">
                                    (revenue share)
                                </span>
                            </Label>
                            <Input
                                id="default_share_percent"
                                v-model="form.default_share_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="0"
                            />
                            <InputError :message="form.errors.default_share_percent" />
                        </div>
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
