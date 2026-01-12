<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/composables/useFormatters';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LineItem {
    quantity: number;
    unit_price: number;
}

interface Props {
    form: InertiaForm<{
        discount_amount: string;
        tax_amount: string;
        shipping_fee: string;
        line_items: LineItem[];
    }>;
}

const props = defineProps<Props>();

const subtotal = computed(() => {
    return props.form.line_items.reduce((sum, item) => {
        return sum + item.quantity * item.unit_price;
    }, 0);
});

const grandTotal = computed(() => {
    const discount = parseFloat(props.form.discount_amount) || 0;
    const tax = parseFloat(props.form.tax_amount) || 0;
    const shipping = parseFloat(props.form.shipping_fee) || 0;
    return subtotal.value - discount + tax + shipping;
});
</script>

<template>
    <div class="space-y-4 rounded-lg border bg-muted/50 p-4">
        <h4 class="font-medium">Pricing & Totals</h4>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-2">
                <Label for="discount_amount">Discount</Label>
                <Input
                    id="discount_amount"
                    v-model="form.discount_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                />
            </div>

            <div class="space-y-2">
                <Label for="tax_amount">Tax</Label>
                <Input
                    id="tax_amount"
                    v-model="form.tax_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                />
            </div>

            <div class="space-y-2">
                <Label for="shipping_fee">Shipping Fee</Label>
                <Input
                    id="shipping_fee"
                    v-model="form.shipping_fee"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                />
            </div>
        </div>

        <div class="space-y-2 border-t pt-4">
            <div class="flex justify-between text-sm">
                <span class="text-muted-foreground">Subtotal:</span>
                <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold">
                <span>Grand Total:</span>
                <span>{{ formatCurrency(grandTotal) }}</span>
            </div>
        </div>
    </div>
</template>
