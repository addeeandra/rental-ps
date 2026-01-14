<script setup lang="ts">
import { Button } from '@/components/ui/button';
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
import type { OrderType, Product } from '@/types/models';
import type { InertiaForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface LineItem {
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    total: number;
}

interface Props {
    form: InertiaForm<{
        line_items: LineItem[];
        order_type: OrderType;
        rental_duration?: number;
    }>;
    products: Product[];
}

const props = defineProps<Props>();

// Calculate the multiplier based on order type and rental duration
const rentalMultiplier = computed(() => {
    if (
        props.form.order_type === 'rental' &&
        props.form.rental_duration &&
        props.form.rental_duration > 0
    ) {
        return props.form.rental_duration;
    }
    return 1;
});

// Calculate line item total with rental duration consideration
function calculateLineTotal(item: LineItem): number {
    return item.quantity * item.unit_price * rentalMultiplier.value;
}

function addLineItem() {
    props.form.line_items.push({
        product_id: null,
        description: '',
        quantity: 1,
        unit_price: 0,
        total: 0,
    });
}

function removeLineItem(index: number) {
    if (props.form.line_items.length > 1) {
        props.form.line_items.splice(index, 1);
    }
}

function onProductChange(index: number) {
    const item = props.form.line_items[index];
    if (item.product_id) {
        const product = props.products.find((p) => p.id === item.product_id);
        if (product) {
            item.description = product.name;
            // Use sales_price for sales orders, rental_price for rental orders
            item.unit_price =
                props.form.order_type === 'sales'
                    ? product.sales_price
                    : product.rental_price;
            item.total = calculateLineTotal(item);
        }
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <Label>Line Items *</Label>
            <Button
                type="button"
                size="sm"
                variant="outline"
                @click="addLineItem"
            >
                <Plus class="mr-2 h-4 w-4" />
                Add Line
            </Button>
        </div>

        <div class="space-y-3">
            <div
                v-for="(item, index) in form.line_items"
                :key="index"
                class="grid gap-3 rounded-lg border-b p-1.5 md:grid-cols-[1fr,1fr,100px,120px,40px]"
            >
                <div class="space-y-1">
                    <Label class="text-xs">Product</Label>
                    <Select
                        v-model="item.product_id"
                        @update:model-value="onProductChange(index)"
                    >
                        <SelectTrigger class="h-9 w-full min-w-[320px]">
                            <SelectValue placeholder="Select or enter custom" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">Custom Item</SelectItem>
                            <SelectItem
                                v-for="product in products"
                                :key="product.id"
                                :value="product.id"
                            >
                                {{ product.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Description *</Label>
                    <Input
                        v-model="item.description"
                        placeholder="Item description"
                        class="h-9"
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Qty *</Label>
                    <Input
                        v-model.number="item.quantity"
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="h-9"
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs">Unit Price *</Label>
                    <Input
                        v-model.number="item.unit_price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="h-9"
                    />
                </div>

                <div class="flex items-end">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9"
                        :disabled="form.line_items.length === 1"
                        @click="removeLineItem(index)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>

                <div
                    class="text-right text-sm text-muted-foreground md:col-span-5"
                >
                    <span
                        v-if="
                            form.order_type === 'rental' && rentalMultiplier > 1
                        "
                        class="mr-2 text-xs text-muted-foreground"
                    >
                        ({{ item.quantity }} ×
                        {{ formatCurrency(item.unit_price) }} ×
                        {{ rentalMultiplier }} days)
                    </span>
                    <span class="font-medium">
                        Total:
                        {{
                            formatCurrency(
                                item.quantity *
                                    item.unit_price *
                                    rentalMultiplier,
                            )
                        }}
                    </span>
                </div>
            </div>
        </div>
        <p v-if="form.errors.line_items" class="text-sm text-destructive">
            {{ form.errors.line_items }}
        </p>
    </div>
</template>
