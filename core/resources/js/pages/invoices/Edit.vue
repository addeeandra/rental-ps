<script setup lang="ts">
import DeleteConfirmDialog from '@/components/invoices/DeleteConfirmDialog.vue';
import InvoiceFormFields from '@/components/invoices/InvoiceFormFields.vue';
import InvoiceLineItems from '@/components/invoices/InvoiceLineItems.vue';
import InvoicePricingSummary from '@/components/invoices/InvoicePricingSummary.vue';
import UpdatePaymentDialog from '@/components/invoices/UpdatePaymentDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Invoice, OrderType, Partner, Product } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileText, MoreVertical, Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    invoice: Invoice;
    partners: Partner[];
    products: Product[];
}

const props = defineProps<Props>();

const showPaymentDialog = ref(false);
const showDeleteDialog = ref(false);

const breadcrumbs = [
    { label: 'Invoices', href: '/invoices' },
    {
        label: props.invoice.invoice_number,
        href: `/invoices/${props.invoice.id}/edit`,
    },
];

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
    partner_id: String(props.invoice.partner_id),
    invoice_number: props.invoice.invoice_number || '',
    reference_number: props.invoice.reference_number || '',
    invoice_date: props.invoice.invoice_date.split('T')[0],
    due_date: props.invoice.due_date.split('T')[0],
    order_type: props.invoice.order_type as OrderType,
    rental_start_date: props.invoice.rental_start_date?.split('T')[0] || '',
    rental_end_date: props.invoice.rental_end_date?.split('T')[0] || '',
    delivery_time: props.invoice.delivery_time?.substring(0, 5) || '09:00',
    return_time: props.invoice.return_time?.substring(0, 5) || '17:00',
    notes: props.invoice.notes || '',
    terms: props.invoice.terms || '',
    discount_amount: String(props.invoice.discount_amount || 0),
    tax_amount: String(props.invoice.tax_amount || 0),
    shipping_fee: String(props.invoice.shipping_fee || 0),
    line_items: (props.invoice.invoice_items?.map((item) => ({
        product_id: item.product_id,
        description: item.description,
        quantity: Number(item.quantity),
        unit_price: Number(item.unit_price),
    })) || [
        { product_id: null, description: '', quantity: 1, unit_price: 0 },
    ]) as LineItem[],
});

watch(
    () => form.invoice_date,
    (newDate) => {
        if (newDate) {
            form.due_date = calculateDueDate(newDate);
        }
    },
);

function save(continueEditing: boolean = false) {
    form.transform((data) => ({
        ...data,
        continue_editing: continueEditing,
    })).patch(`/invoices/${props.invoice.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/invoices');
}

function openPreviewPdf() {
    window.open(`/invoices/${props.invoice.id}/preview`, '_blank');
}

function openPreviewHtml() {
    window.open(`/invoices/${props.invoice.id}/preview-html`, '_blank');
}

function getStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'paid':
            return 'default';
        case 'partial':
            return 'secondary';
        case 'unpaid':
            return 'outline';
        case 'void':
            return 'destructive';
        default:
            return 'outline';
    }
}

function getStatusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}
</script>

<template>
    <Head :title="`Edit Invoice - ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto space-y-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <FileText class="h-8 w-8 text-muted-foreground" />
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ invoice.invoice_number }}
                        </h1>
                        <div class="mt-1 flex items-center gap-2">
                            <Badge :variant="getStatusVariant(invoice.status)">
                                {{ getStatusLabel(invoice.status) }}
                            </Badge>
                            <Badge variant="outline">
                                {{
                                    invoice.order_type === 'sales'
                                        ? 'Sales'
                                        : 'Rental'
                                }}
                            </Badge>
                            <span
                                v-if="!invoice.is_editable"
                                class="text-sm text-destructive"
                            >
                                (Read-only - Cannot edit paid/void invoices)
                            </span>
                        </div>
                    </div>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon">
                            <MoreVertical class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="showPaymentDialog = true">
                            Update Payment
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="openPreviewPdf">
                            Preview PDF
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="openPreviewHtml">
                            Preview HTML (Debug)
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="invoice.is_editable"
                            class="text-destructive"
                            @click="showDeleteDialog = true"
                        >
                            Delete Invoice
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Form -->
            <Card>
                <CardContent class="space-y-6 pt-6">
                    <InvoiceFormFields
                        :form="form"
                        :partners="partners"
                        :time-options="timeOptions"
                    />

                    <InvoiceLineItems :form="form" :products="products" />

                    <InvoicePricingSummary :form="form" />
                </CardContent>
            </Card>

            <!-- Action Bar -->
            <div
                class="sticky bottom-0 flex items-center justify-between gap-4 border-t bg-background p-4"
            >
                <Button type="button" variant="outline" @click="cancel">
                    Cancel
                </Button>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="save(true)"
                        :disabled="form.processing || !invoice.is_editable"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        Save & Continue Edit
                    </Button>
                    <Button
                        type="button"
                        @click="save(false)"
                        :disabled="form.processing || !invoice.is_editable"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        Save
                    </Button>
                </div>
            </div>
        </div>

        <!-- Dialogs -->
        <UpdatePaymentDialog
            v-if="showPaymentDialog"
            v-model:open="showPaymentDialog"
            :invoice="invoice"
            @success="showPaymentDialog = false"
            @cancel="showPaymentDialog = false"
        />

        <DeleteConfirmDialog
            v-if="showDeleteDialog"
            v-model:open="showDeleteDialog"
            :invoice="invoice"
            @success="router.visit('/invoices')"
            @cancel="showDeleteDialog = false"
        />
    </AppLayout>
</template>
