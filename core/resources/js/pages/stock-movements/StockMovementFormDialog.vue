<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import type { InventoryItem, Warehouse } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    open: boolean;
    inventoryItems: InventoryItem[];
    warehouses: Warehouse[];
    reasons: Record<string, string>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => {
        emit('update:open', value);
    },
});

const form = useForm({
    inventory_item_id: '',
    warehouse_id: '',
    quantity: '',
    reason: 'adjustment',
    notes: '',
});

function submit() {
    form.post('/stock-movements', {
        onSuccess: () => {
            emit('success');
            form.reset();
        },
    });
}

function handleClose() {
    isOpen.value = false;
    form.reset();
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>New Stock Movement</DialogTitle>
                <DialogDescription>
                    Record a stock adjustment or movement. Use positive values to add stock, negative to remove.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="inventory_item_id">Inventory Item *</Label>
                        <select
                            id="inventory_item_id"
                            v-model="form.inventory_item_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Select item...</option>
                            <option
                                v-for="item in inventoryItems"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.sku }} - {{ item.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.inventory_item_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="warehouse_id">Warehouse *</Label>
                        <select
                            id="warehouse_id"
                            v-model="form.warehouse_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Select warehouse...</option>
                            <option
                                v-for="warehouse in warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                            >
                                {{ warehouse.code }} - {{ warehouse.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.warehouse_id" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="quantity">
                                Quantity *
                                <span class="text-xs font-normal text-muted-foreground">
                                    (+ to add, - to remove)
                                </span>
                            </Label>
                            <Input
                                id="quantity"
                                v-model="form.quantity"
                                type="number"
                                step="0.001"
                                placeholder="0"
                                required
                            />
                            <InputError :message="form.errors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="reason">Reason *</Label>
                            <select
                                id="reason"
                                v-model="form.reason"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option
                                    v-for="(label, value) in reasons"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.reason" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Optional notes about this movement..."
                            rows="3"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Record Movement
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
