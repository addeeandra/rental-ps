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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCurrency } from '@/composables/useFormatters';
import type { Invoice, InvoiceStatus } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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

const form = useForm({
    paid_amount: props.invoice.paid_amount.toString(),
    status: props.invoice.status as InvoiceStatus | '',
});

watch(
    () => props.open,
    (value) => {
        if (value) {
            form.paid_amount = props.invoice.paid_amount.toString();
            form.status = '';
        }
    },
);

const suggestedStatus = computed(() => {
    const paid = parseFloat(form.paid_amount) || 0;
    if (paid <= 0) return 'unpaid';
    if (paid >= props.invoice.total_amount) return 'paid';
    return 'partial';
});

const newBalance = computed(() => {
    const paid = parseFloat(form.paid_amount) || 0;
    return props.invoice.total_amount - paid;
});

function submit() {
    form.patch(`/invoices/${props.invoice.id}/payment`, {
        onSuccess: () => {
            emit('success');
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Update Payment</DialogTitle>
                <DialogDescription>
                    Update the payment status for invoice
                    {{ invoice.invoice_number }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Invoice Summary -->
                <div class="space-y-2 rounded-lg bg-muted p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Total Amount:</span>
                        <span class="font-medium">{{
                            formatCurrency(invoice.total_amount)
                        }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Current Paid:</span>
                        <span class="font-medium">{{
                            formatCurrency(invoice.paid_amount)
                        }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-sm">
                        <span class="text-muted-foreground"
                            >Current Balance:</span
                        >
                        <span class="font-medium">{{
                            formatCurrency(invoice.balance)
                        }}</span>
                    </div>
                </div>

                <!-- Paid Amount Input -->
                <div class="space-y-2">
                    <Label for="paid_amount">Paid Amount</Label>
                    <Input
                        id="paid_amount"
                        v-model="form.paid_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :class="{
                            'border-destructive': form.errors.paid_amount,
                        }"
                    />
                    <p
                        v-if="form.errors.paid_amount"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.paid_amount }}
                    </p>
                </div>

                <!-- New Balance Preview -->
                <div class="space-y-2 rounded-lg border p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">New Balance:</span>
                        <span
                            class="font-medium"
                            :class="
                                newBalance <= 0
                                    ? 'text-green-600'
                                    : 'text-orange-600'
                            "
                        >
                            {{ formatCurrency(newBalance) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground"
                            >Suggested Status:</span
                        >
                        <span class="font-medium capitalize">{{
                            suggestedStatus
                        }}</span>
                    </div>
                </div>

                <!-- Status Override (Optional) -->
                <div class="space-y-2">
                    <Label for="status"
                        >Manual Status Override (Optional)</Label
                    >
                    <Select v-model="form.status">
                        <SelectTrigger id="status">
                            <SelectValue
                                placeholder="Auto-calculate based on paid amount"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="auto">Auto-calculate</SelectItem>
                            <SelectItem value="unpaid">Unpaid</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="void">Void</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Leave empty to auto-calculate status based on paid
                        amount
                    </p>
                    <p
                        v-if="form.errors.status"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.status }}
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('cancel')">
                    Cancel
                </Button>
                <Button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                >
                    Update Payment
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
