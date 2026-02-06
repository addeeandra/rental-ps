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
import type {
    InventoryItem,
    OrderType,
    Product,
    Warehouse,
} from '@/types/models';
import type { InertiaForm } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface LineItemComponent {
    inventory_item_id: number;
    warehouse_id: number;
    qty: number;
    share_percent?: number | null;
}

interface LineItem {
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    total: number;
    components: LineItemComponent[];
}

interface Props {
    form: InertiaForm<{
        line_items: LineItem[];
        order_type: OrderType;
        rental_duration?: number;
    }>;
    products: Product[];
    warehouses: Warehouse[];
    inventoryItems: InventoryItem[];
}

const props = defineProps<Props>();

// Track which line items have components expanded
const expandedComponents = ref<Set<number>>(new Set());

function toggleComponentsExpanded(index: number) {
    if (expandedComponents.value.has(index)) {
        expandedComponents.value.delete(index);
    } else {
        expandedComponents.value.add(index);
    }
}

function addComponent(lineItemIndex: number) {
    const item = props.form.line_items[lineItemIndex];
    if (item.components.length < 2) {
        const defaultWarehouse =
            props.warehouses.find((w) => w.is_active) || props.warehouses[0];
        item.components.push({
            inventory_item_id: 0,
            warehouse_id: defaultWarehouse?.id || 0,
            qty: 1,
            share_percent: null,
        });
    }
}

function removeComponent(lineItemIndex: number, componentIndex: number) {
    props.form.line_items[lineItemIndex].components.splice(componentIndex, 1);
}

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
        components: [],
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

            // Auto-populate components from product if available
            if (
                product.product_components &&
                product.product_components.length > 0
            ) {
                // Get default warehouse (first active warehouse)
                const defaultWarehouse = props.warehouses.find(
                    (w) => w.is_active,
                );

                item.components = product.product_components.map((pc) => ({
                    inventory_item_id: pc.inventory_item_id,
                    warehouse_id:
                        defaultWarehouse?.id || props.warehouses[0]?.id || 0,
                    qty: pc.qty_per_product,
                    share_percent: null, // Will use default from inventory item
                }));
            } else {
                item.components = [];
            }
        }
    } else {
        // If no product selected, clear components
        item.components = [];
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
                class="rounded-lg border p-3"
            >
                <div class="grid gap-3 md:grid-cols-[1fr,1fr,100px,120px,40px]">
                    <div class="space-y-1">
                        <Label class="text-xs">Product</Label>
                        <Select
                            v-model="item.product_id"
                            @update:model-value="onProductChange(index)"
                        >
                            <SelectTrigger class="h-9 w-full min-w-[320px]">
                                <SelectValue
                                    placeholder="Select or enter custom"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >Custom Item</SelectItem
                                >
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
                                form.order_type === 'rental' &&
                                rentalMultiplier > 1
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

                <!-- Components Section (Optional) -->
                <div class="mt-3 border-t pt-3">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 text-sm font-medium hover:underline"
                        @click="toggleComponentsExpanded(index)"
                    >
                        <component
                            :is="
                                expandedComponents.has(index)
                                    ? ChevronUp
                                    : ChevronDown
                            "
                            class="h-4 w-4"
                        />
                        <span>
                            Components (Optional)
                            <span
                                v-if="item.components.length > 0"
                                class="ml-1 text-xs text-muted-foreground"
                            >
                                - {{ item.components.length }} configured
                            </span>
                        </span>
                    </button>

                    <div
                        v-if="expandedComponents.has(index)"
                        class="mt-3 space-y-3"
                    >
                        <div
                            v-for="(component, compIndex) in item.components"
                            :key="compIndex"
                            class="grid gap-2 rounded-lg border bg-muted/30 p-3 md:grid-cols-[1fr,1fr,100px,120px,40px]"
                        >
                            <div class="space-y-1">
                                <Label class="text-xs">Inventory Item *</Label>
                                <Select v-model="component.inventory_item_id">
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            placeholder="Select item"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="invItem in inventoryItems"
                                            :key="invItem.id"
                                            :value="invItem.id"
                                        >
                                            [{{ invItem.sku }}]
                                            {{ invItem.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1">
                                <Label class="text-xs">Warehouse *</Label>
                                <Select v-model="component.warehouse_id">
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            placeholder="Select warehouse"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="warehouse in warehouses"
                                            :key="warehouse.id"
                                            :value="warehouse.id"
                                        >
                                            {{ warehouse.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1">
                                <Label class="text-xs">Qty *</Label>
                                <Input
                                    v-model.number="component.qty"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    class="h-9"
                                />
                            </div>

                            <div class="space-y-1">
                                <Label class="text-xs"
                                    >Share % (Optional)</Label
                                >
                                <Input
                                    v-model.number="component.share_percent"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="Auto"
                                    class="h-9"
                                />
                            </div>

                            <div class="flex items-end">
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    class="h-9 w-9"
                                    @click="removeComponent(index, compIndex)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <Button
                            v-if="item.components.length < 2"
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="addComponent(index)"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Add Component
                        </Button>

                        <p
                            v-if="item.components.length >= 2"
                            class="text-xs text-muted-foreground"
                        >
                            Maximum 2 components per line item
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="form.errors.line_items" class="text-sm text-destructive">
            {{ form.errors.line_items }}
        </p>
    </div>
</template>
