<script setup lang="ts">
import HeadingFormSection from '@/components/HeadingFormSection.vue';
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
import type { Category, InventoryItem, Product } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean | Product;
    product?: Product | null;
    categories: Category[];
    inventoryItems: InventoryItem[];
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

const isEditing = computed(() => !!props.product);
const showComponents = ref(false);

const form = useForm<{
    code: string;
    name: string;
    description: string;
    category_id: string;
    sales_price: string;
    rental_price: string;
    uom: string;
    rental_duration: string;
    components: Array<{
        inventory_item_id: number | string;
        slot: number;
        qty_per_product: number | string;
    }>;
}>({
    code: props.product?.code || '',
    name: props.product?.name || '',
    description: props.product?.description || '',
    category_id: props.product?.category_id?.toString() || '',
    sales_price: props.product?.sales_price?.toString() || '0',
    rental_price: props.product?.rental_price?.toString() || '0',
    uom: props.product?.uom || 'pcs',
    rental_duration: props.product?.rental_duration || 'day',
    components: [],
});

function addComponent(slot: number) {
    form.components.push({
        inventory_item_id: '',
        slot,
        qty_per_product: '1',
    });
}

function removeComponent(index: number) {
    form.components.splice(index, 1);
}

function submit() {
    if (isEditing.value && props.product) {
        form.patch(`/products/${props.product.id}`, {
            onSuccess: () => {
                emit('success');
                form.reset();
                showComponents.value = false;
            },
        });
    } else {
        form.post('/products', {
            onSuccess: () => {
                emit('success');
                form.reset();
                showComponents.value = false;
            },
        });
    }
}

watch(
    () => props.open,
    (value) => {
        if (value && props.product) {
            form.code = props.product.code;
            form.name = props.product.name;
            form.description = props.product.description || '';
            form.category_id = props.product.category_id?.toString() || '';
            form.sales_price = props.product.sales_price?.toString() || '0';
            form.rental_price = props.product.rental_price?.toString() || '0';
            form.uom = props.product.uom;
            form.rental_duration = props.product.rental_duration;

            // Load existing components
            form.components = (props.product.product_components || []).map(
                (pc) => ({
                    inventory_item_id: pc.inventory_item_id,
                    slot: pc.slot,
                    qty_per_product: pc.qty_per_product.toString(),
                }),
            );
            showComponents.value = form.components.length > 0;
        } else if (!value) {
            form.reset();
            form.components = [];
            showComponents.value = false;
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Product' : 'New Product'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update product information.'
                            : 'Add a new product to your catalog.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <HeadingFormSection title="Basic Information" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="code">
                                Code
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    (Optional)
                                </span>
                            </Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                :placeholder="
                                    isEditing ? '' : 'Auto-generated (PRD-XXXX)'
                                "
                            />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="category_id"
                                >Category
                                <span class="text-destructive">*</span></Label
                            >
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="" disabled>
                                    Select a category
                                </option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Product name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Product description"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <!-- Pricing Information -->
                <div class="space-y-4">
                    <HeadingFormSection title="Pricing & Details" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="sales_price"
                                >Sales Price
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="sales_price"
                                v-model="form.sales_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                placeholder="0"
                            />
                            <InputError :message="form.errors.sales_price" />
                            <p class="text-xs text-muted-foreground">
                                Required for selling product (not rental).
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="rental_price"
                                >Rental Price
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="rental_price"
                                v-model="form.rental_price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                placeholder="0"
                            />
                            <InputError :message="form.errors.rental_price" />
                            <p class="text-xs text-muted-foreground">
                                Required for rental product.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="rental_duration"
                                >Rental Duration
                                <span class="text-destructive">*</span></Label
                            >
                            <select
                                id="rental_duration"
                                v-model="form.rental_duration"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="hour">hour</option>
                                <option value="day">day</option>
                                <option value="week">week</option>
                                <option value="month">month</option>
                            </select>
                            <InputError
                                :message="form.errors.rental_duration"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="uom"
                                >Unit of Measure
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="uom"
                                v-model="form.uom"
                                required
                                placeholder="pcs, unit, set, etc."
                            />
                            <InputError :message="form.errors.uom" />
                        </div>
                    </div>
                </div>

                <!-- Components (Optional) -->
                <div class="space-y-4">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg border border-input bg-background p-3 text-left hover:bg-accent"
                        @click="showComponents = !showComponents"
                    >
                        <div>
                            <p class="font-medium">Components (Optional)</p>
                            <p class="text-xs text-muted-foreground">
                                Link inventory items to this product for stock
                                tracking
                            </p>
                        </div>
                        <ChevronDown v-if="!showComponents" class="h-4 w-4" />
                        <ChevronUp v-else class="h-4 w-4" />
                    </button>

                    <div v-if="showComponents" class="space-y-4">
                        <!-- Slot 1 -->
                        <div class="space-y-3 rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm font-medium"
                                    >Slot 1</Label
                                >
                                <Button
                                    v-if="
                                        !form.components.find(
                                            (c) => c.slot === 1,
                                        )
                                    "
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addComponent(1)"
                                >
                                    Add Component
                                </Button>
                            </div>

                            <div
                                v-for="(
                                    component, index
                                ) in form.components.filter(
                                    (c) => c.slot === 1,
                                )"
                                :key="`slot1-${index}`"
                                class="space-y-3 border-t pt-3"
                            >
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 space-y-3">
                                        <div class="grid gap-2">
                                            <Label
                                                :for="`component-${index}-item`"
                                            >
                                                Inventory Item
                                                <span class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                            <select
                                                :id="`component-${index}-item`"
                                                v-model="
                                                    component.inventory_item_id
                                                "
                                                required
                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <option value="" disabled>
                                                    Select an item
                                                </option>
                                                <option
                                                    v-for="item in inventoryItems"
                                                    :key="item.id"
                                                    :value="item.id"
                                                >
                                                    {{ item.sku }} -
                                                    {{ item.name }}
                                                    <span v-if="item.owner"
                                                        >({{
                                                            item.owner.name
                                                        }})</span
                                                    >
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `components.${index}.inventory_item_id`
                                                    ]
                                                "
                                            />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label
                                                :for="`component-${index}-qty`"
                                            >
                                                Qty per Product
                                                <span class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                            <Input
                                                :id="`component-${index}-qty`"
                                                v-model="
                                                    component.qty_per_product
                                                "
                                                type="number"
                                                min="0.001"
                                                step="0.001"
                                                required
                                                placeholder="1"
                                            />
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `components.${index}.qty_per_product`
                                                    ]
                                                "
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="mt-8"
                                        @click="removeComponent(index)"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Slot 2 -->
                        <div class="space-y-3 rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm font-medium"
                                    >Slot 2</Label
                                >
                                <Button
                                    v-if="
                                        !form.components.find(
                                            (c) => c.slot === 2,
                                        )
                                    "
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addComponent(2)"
                                >
                                    Add Component
                                </Button>
                            </div>

                            <div
                                v-for="(
                                    component, index
                                ) in form.components.filter(
                                    (c) => c.slot === 2,
                                )"
                                :key="`slot2-${index}`"
                                class="space-y-3 border-t pt-3"
                            >
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 space-y-3">
                                        <div class="grid gap-2">
                                            <Label
                                                :for="`component-${form.components.indexOf(component)}-item`"
                                            >
                                                Inventory Item
                                                <span class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                            <select
                                                :id="`component-${form.components.indexOf(component)}-item`"
                                                v-model="
                                                    component.inventory_item_id
                                                "
                                                required
                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <option value="" disabled>
                                                    Select an item
                                                </option>
                                                <option
                                                    v-for="item in inventoryItems"
                                                    :key="item.id"
                                                    :value="item.id"
                                                >
                                                    {{ item.sku }} -
                                                    {{ item.name }}
                                                    <span v-if="item.owner"
                                                        >({{
                                                            item.owner.name
                                                        }})</span
                                                    >
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `components.${form.components.indexOf(component)}.inventory_item_id`
                                                    ]
                                                "
                                            />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label
                                                :for="`component-${form.components.indexOf(component)}-qty`"
                                            >
                                                Qty per Product
                                                <span class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                            <Input
                                                :id="`component-${form.components.indexOf(component)}-qty`"
                                                v-model="
                                                    component.qty_per_product
                                                "
                                                type="number"
                                                min="0.001"
                                                step="0.001"
                                                required
                                                placeholder="1"
                                            />
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `components.${form.components.indexOf(component)}.qty_per_product`
                                                    ]
                                                "
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="mt-8"
                                        @click="
                                            removeComponent(
                                                form.components.indexOf(
                                                    component,
                                                ),
                                            )
                                        "
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Components link inventory items to this product.
                            When you create an invoice with this product, stock
                            will be deducted from the selected warehouse, and
                            revenue will be shared with the component owners.
                        </p>
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
