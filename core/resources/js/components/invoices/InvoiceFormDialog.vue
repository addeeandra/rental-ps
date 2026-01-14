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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/composables/useFormatters';
import PartnerFormDialog from '@/pages/partners/PartnerFormDialog.vue';
import type { Invoice, OrderType, Partner, Product } from '@/types/models';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    invoice?: Invoice | null;
    partners: Partner[];
    products: Product[];
    defaultInvoiceTerms?: string | null;
    defaultInvoiceNotes?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    success: [];
    cancel: [];
}>();

const isOpen = defineModel<boolean>('open', { required: true });

const isEditing = computed(() => Boolean(props.invoice));
const showPartnerDialog = ref(false);

// Generate time options (00:00 to 23:30 in 30-minute intervals)
const timeOptions = computed(() => {
    const options: string[] = [];
    for (let hour = 0; hour < 24; hour++) {
        for (const minute of [0, 30]) {
            const hourStr = hour.toString().padStart(2, '0');
            const minuteStr = minute.toString().padStart(2, '0');
            options.push(`${hourStr}:${minuteStr}`);
        }
    }
    return options;
});

// Calculate due date (30 days from invoice date)
const calculateDueDate = (invoiceDate: string): string => {
    const date = new Date(invoiceDate);
    date.setDate(date.getDate() + 30);
    return date.toISOString().split('T')[0];
};

interface LineItem {
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
}

const form = useForm({
    partner_id: '',
    invoice_number: '',
    reference_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: calculateDueDate(new Date().toISOString().split('T')[0]),
    order_type: 'sales' as OrderType,
    rental_start_date: '',
    rental_end_date: '',
    delivery_time: '09:00',
    return_time: '17:00',
    notes: '',
    terms: '',
    discount_amount: '0',
    tax_amount: '0',
    shipping_fee: '0',
    line_items: [
        { product_id: null, description: '', quantity: 1, unit_price: 0 },
    ] as LineItem[],
});

watch(
    () => form.invoice_date,
    (newDate) => {
        if (newDate && !isEditing.value) {
            form.due_date = calculateDueDate(newDate);
        }
    },
);

const subtotal = computed(() => {
    return form.line_items.reduce((sum, item) => {
        return sum + item.quantity * item.unit_price;
    }, 0);
});

const grandTotal = computed(() => {
    const discount = parseFloat(form.discount_amount) || 0;
    const tax = parseFloat(form.tax_amount) || 0;
    const shipping = parseFloat(form.shipping_fee) || 0;
    return subtotal.value - discount + tax + shipping;
});

function addLineItem() {
    form.line_items.push({
        product_id: null,
        description: '',
        quantity: 1,
        unit_price: 0,
    });
}

function removeLineItem(index: number) {
    if (form.line_items.length > 1) {
        form.line_items.splice(index, 1);
    }
}

function onProductChange(index: number) {
    const item = form.line_items[index];
    if (item.product_id) {
        const product = props.products.find((p) => p.id === item.product_id);
        if (product) {
            item.description = product.name;
            // Use sales_price for sales orders, rental_price for rental orders
            item.unit_price =
                form.order_type === 'sales'
                    ? product.sales_price
                    : product.rental_price;
        }
    }
}

function handlePartnerCreated() {
    router.reload({ only: ['partners'] });
}

function submit() {
    const url = isEditing.value
        ? `/invoices/${props.invoice!.id}`
        : '/invoices';
    const method = isEditing.value ? 'patch' : 'post';

    form[method](url, {
        onSuccess: () => {
            emit('success');
            form.reset();
        },
    });
}

onMounted(() => {
    if (!open) return; // Don't do anything if dialog is closed

    if (!props.invoice) {
        // Reset form for create mode
        form.partner_id = '';
        form.invoice_number = '';
        form.reference_number = '';
        form.invoice_date = new Date().toISOString().split('T')[0];
        form.due_date = calculateDueDate(form.invoice_date);
        form.order_type = 'sales';
        form.rental_start_date = '';
        form.rental_end_date = '';
        form.delivery_time = '09:00';
        form.return_time = '17:00';
        form.notes = props.defaultInvoiceNotes || '';
        form.terms = props.defaultInvoiceTerms || '';
        form.discount_amount = '0';
        form.tax_amount = '0';
        form.shipping_fee = '0';
        form.line_items = [
            {
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
            },
        ];
    } else {
        console.debug('Editing invoice:', props.invoice);

        // Populate form for edit mode - convert partner_id to string for Select
        form.partner_id = String(props.invoice.partner_id);
        form.invoice_number = props.invoice.invoice_number || '';
        form.reference_number = props.invoice.reference_number || '';
        form.invoice_date = props.invoice.invoice_date.split('T')[0];
        form.due_date = props.invoice.due_date.split('T')[0];
        form.order_type = props.invoice.order_type;
        form.rental_start_date =
            props.invoice.rental_start_date?.split('T')[0] || '';
        form.rental_end_date =
            props.invoice.rental_end_date?.split('T')[0] || '';
        form.delivery_time =
            props.invoice.delivery_time?.substring(0, 5) || '09:00';
        form.return_time =
            props.invoice.return_time?.substring(0, 5) || '17:00';
        form.notes = props.invoice.notes || '';
        form.terms = props.invoice.terms || '';
        form.discount_amount = String(props.invoice.discount_amount || 0);
        form.tax_amount = String(props.invoice.tax_amount || 0);
        form.shipping_fee = String(props.invoice.shipping_fee || 0);

        // Properly map line items
        if (
            props.invoice.invoice_items &&
            props.invoice.invoice_items.length > 0
        ) {
            console.debug(
                'Populating line items:',
                props.invoice.invoice_items,
            );
            form.line_items = props.invoice.invoice_items.map((item) => ({
                product_id: item.product_id,
                description: item.description,
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price),
            }));
        } else {
            form.line_items = [
                {
                    product_id: null,
                    description: '',
                    quantity: 1,
                    unit_price: 0,
                },
            ];
        }
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="max-h-[90vh] max-w-5xl overflow-y-auto sm:max-w-7xl"
        >
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Invoice' : 'Create New Invoice'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update invoice details'
                            : 'Fill in the details to create a new invoice'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Customer Selection -->
                <div class="space-y-2">
                    <Label for="partner_id">Customer *</Label>
                    <div class="flex gap-2">
                        <Select v-model="form.partner_id" class="flex-1">
                            <SelectTrigger
                                id="partner_id"
                                :class="{
                                    'border-destructive':
                                        form.errors.partner_id,
                                }"
                            >
                                <SelectValue placeholder="Select a customer" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="partner in partners"
                                    :key="partner.id"
                                    :value="String(partner.id)"
                                >
                                    {{ partner.name }} ({{ partner.code }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            @click="showPartnerDialog = true"
                        >
                            <Plus class="h-4 w-4" />
                        </Button>
                    </div>
                    <p
                        v-if="form.errors.partner_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.partner_id }}
                    </p>
                </div>

                <!-- Invoice Code -->
                <div class="space-y-2">
                    <Label for="invoice_number">Invoice Code</Label>
                    <Input
                        id="invoice_number"
                        v-model="form.invoice_number"
                        placeholder="Leave empty for auto-generation (e.g., INV-2026-0001)"
                        :class="{
                            'border-destructive': form.errors.invoice_number,
                        }"
                    />
                    <p
                        v-if="form.errors.invoice_number"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.invoice_number }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Optional: Leave blank to auto-generate, or enter a
                        custom code
                    </p>
                </div>

                <!-- Invoice Details Grid -->
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="invoice_date">Invoice Date *</Label>
                        <Input
                            id="invoice_date"
                            v-model="form.invoice_date"
                            type="date"
                            :class="{
                                'border-destructive': form.errors.invoice_date,
                            }"
                        />
                        <p
                            v-if="form.errors.invoice_date"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.invoice_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="due_date">Due Date *</Label>
                        <Input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            :class="{
                                'border-destructive': form.errors.due_date,
                            }"
                        />
                        <p
                            v-if="form.errors.due_date"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.due_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="reference_number">Reference Number</Label>
                        <Input
                            id="reference_number"
                            v-model="form.reference_number"
                            placeholder="Optional"
                        />
                    </div>
                </div>

                <!-- Order Type -->
                <div class="space-y-2">
                    <Label>Order Type *</Label>
                    <RadioGroup v-model="form.order_type" class="flex gap-4">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="sales" id="sales" />
                            <Label
                                for="sales"
                                class="cursor-pointer font-normal"
                                >Sales</Label
                            >
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="rental" id="rental" />
                            <Label
                                for="rental"
                                class="cursor-pointer font-normal"
                                >Rental</Label
                            >
                        </div>
                    </RadioGroup>
                </div>

                <!-- Rental Fields (Conditional) -->
                <div
                    v-if="form.order_type === 'rental'"
                    class="space-y-4 rounded-lg border bg-muted/50 p-4"
                >
                    <h4 class="font-medium">Rental Information</h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="rental_start_date"
                                >Rental Start Date *</Label
                            >
                            <Input
                                id="rental_start_date"
                                v-model="form.rental_start_date"
                                type="date"
                                :class="{
                                    'border-destructive':
                                        form.errors.rental_start_date,
                                }"
                            />
                            <p
                                v-if="form.errors.rental_start_date"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.rental_start_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="rental_end_date"
                                >Rental End Date *</Label
                            >
                            <Input
                                id="rental_end_date"
                                v-model="form.rental_end_date"
                                type="date"
                                :class="{
                                    'border-destructive':
                                        form.errors.rental_end_date,
                                }"
                            />
                            <p
                                v-if="form.errors.rental_end_date"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.rental_end_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="delivery_time">Delivery Time *</Label>
                            <Select v-model="form.delivery_time">
                                <SelectTrigger
                                    id="delivery_time"
                                    :class="{
                                        'border-destructive':
                                            form.errors.delivery_time,
                                    }"
                                >
                                    <SelectValue placeholder="Select time" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="time in timeOptions"
                                        :key="time"
                                        :value="time"
                                    >
                                        {{ time }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.delivery_time"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.delivery_time }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="return_time">Return Time *</Label>
                            <Select v-model="form.return_time">
                                <SelectTrigger
                                    id="return_time"
                                    :class="{
                                        'border-destructive':
                                            form.errors.return_time,
                                    }"
                                >
                                    <SelectValue placeholder="Select time" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="time in timeOptions"
                                        :key="time"
                                        :value="time"
                                    >
                                        {{ time }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.return_time"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.return_time }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
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
                            class="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr,1fr,100px,120px,40px]"
                        >
                            <div class="space-y-1">
                                <Label class="text-xs">Product</Label>
                                <Select
                                    v-model="item.product_id"
                                    @update:model-value="onProductChange(index)"
                                >
                                    <SelectTrigger class="h-9">
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
                                Total:
                                {{
                                    formatCurrency(
                                        item.quantity * item.unit_price,
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.line_items"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.line_items }}
                    </p>
                </div>

                <!-- Totals Section -->
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
                            <span class="font-medium">{{
                                formatCurrency(subtotal)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold">
                            <span>Grand Total:</span>
                            <span>{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes and Terms -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Additional notes..."
                            rows="3"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="terms">Terms & Conditions</Label>
                        <Textarea
                            id="terms"
                            v-model="form.terms"
                            placeholder="Terms and conditions..."
                            rows="3"
                        />
                    </div>
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
                    {{ isEditing ? 'Update Invoice' : 'Create Invoice' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Nested Partner Dialog -->
    <PartnerFormDialog
        v-model:open="showPartnerDialog"
        @success="handlePartnerCreated"
    />
</template>
