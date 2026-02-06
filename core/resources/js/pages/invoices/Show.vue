<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/composables/useFormatters';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Invoice } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Edit, FileText } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    invoice: Invoice;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Invoices', href: '/invoices' },
    {
        label: props.invoice.invoice_number,
        href: `/invoices/${props.invoice.id}`,
    },
];

// Track which line items have components expanded
const expandedItems = ref<Set<number>>(new Set());

function toggleItemExpanded(index: number) {
    if (expandedItems.value.has(index)) {
        expandedItems.value.delete(index);
    } else {
        expandedItems.value.add(index);
    }
}

// Calculate revenue shares grouped by owner
const revenueSharesByOwner = computed(() => {
    const shares: Record<
        number,
        {
            owner: { id: number; name: string };
            totalShare: number;
            components: Array<{
                item: string;
                qty: number;
                inventory_item: string;
                warehouse: string;
                share_percent: number;
                share_amount: number;
            }>;
        }
    > = {};

    props.invoice.invoice_items?.forEach((item) => {
        item.invoice_item_components?.forEach((component) => {
            if (!component.owner) return;

            if (!shares[component.owner_id]) {
                shares[component.owner_id] = {
                    owner: {
                        id: component.owner.id,
                        name: component.owner.name,
                    },
                    totalShare: 0,
                    components: [],
                };
            }

            shares[component.owner_id].totalShare += component.share_amount;
            shares[component.owner_id].components.push({
                item: item.description,
                qty: component.qty,
                inventory_item: component.inventory_item?.name || 'N/A',
                warehouse: component.warehouse?.name || 'N/A',
                share_percent: component.share_percent,
                share_amount: component.share_amount,
            });
        });
    });

    return Object.values(shares);
});

const statusVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default';
        case 'partial':
            return 'secondary';
        case 'unpaid':
            return 'destructive';
        case 'void':
            return 'outline';
        default:
            return 'outline';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Invoice ${invoice.invoice_number}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Invoice {{ invoice.invoice_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{
                            invoice.order_type === 'rental' ? 'Rental' : 'Sales'
                        }}
                        Order
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Badge :variant="statusVariant(invoice.status)">
                        {{ invoice.status?.toUpperCase() }}
                    </Badge>

                    <Link
                        v-if="invoice.is_editable"
                        :href="`/invoices/${invoice.id}/edit`"
                    >
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </Link>

                    <a
                        :href="`/invoices/${invoice.id}/preview`"
                        target="_blank"
                    >
                        <Button variant="outline">
                            <FileText class="mr-2 h-4 w-4" />
                            Preview PDF
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Client</p>
                            <p class="font-medium">
                                {{ invoice.partner?.name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">
                                Invoice Date
                            </p>
                            <p class="font-medium">
                                {{ formatDate(invoice.invoice_date) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">
                                Due Date
                            </p>
                            <p class="font-medium">
                                {{ formatDate(invoice.due_date) }}
                            </p>
                        </div>

                        <div v-if="invoice.reference_number">
                            <p class="text-sm text-muted-foreground">
                                Reference Number
                            </p>
                            <p class="font-medium">
                                {{ invoice.reference_number }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Total Amount
                            </p>
                            <p class="text-2xl font-bold">
                                {{ formatCurrency(invoice.total_amount) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">
                                Paid Amount
                            </p>
                            <p class="font-medium">
                                {{ formatCurrency(invoice.paid_amount) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">Balance</p>
                            <p class="font-medium">
                                {{ formatCurrency(invoice.balance) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Rental Information -->
            <Card v-if="invoice.order_type === 'rental'">
                <CardHeader>
                    <CardTitle>Rental Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Start Date
                            </p>
                            <p class="font-medium">
                                {{
                                    invoice.rental_start_date
                                        ? formatDate(invoice.rental_start_date)
                                        : 'N/A'
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">
                                End Date
                            </p>
                            <p class="font-medium">
                                {{
                                    invoice.rental_end_date
                                        ? formatDate(invoice.rental_end_date)
                                        : 'N/A'
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-muted-foreground">
                                Duration
                            </p>
                            <p class="font-medium">
                                {{ invoice.rental_days }} days
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Line Items -->
            <Card>
                <CardHeader>
                    <CardTitle>Line Items</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Description</TableHead>
                                <TableHead class="text-right">Qty</TableHead>
                                <TableHead class="text-right">
                                    Unit Price
                                </TableHead>
                                <TableHead class="text-right">Total</TableHead>
                                <TableHead class="w-10"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template
                                v-for="(item, index) in invoice.invoice_items"
                                :key="item.id"
                            >
                                <!-- Line Item Row -->
                                <TableRow>
                                    <TableCell class="font-medium">
                                        <div>{{ item.description }}</div>
                                        <div
                                            v-if="item.product"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.product.code }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ item.quantity }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ formatCurrency(item.unit_price) }}
                                    </TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ formatCurrency(item.total) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Button
                                            v-if="
                                                item.invoice_item_components &&
                                                item.invoice_item_components
                                                    .length > 0
                                            "
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8"
                                            @click="toggleItemExpanded(index)"
                                        >
                                            <component
                                                :is="
                                                    expandedItems.has(index)
                                                        ? ChevronUp
                                                        : ChevronDown
                                                "
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <!-- Components Row (Expanded) -->
                                <TableRow v-if="expandedItems.has(index)">
                                    <TableCell colspan="5" class="bg-muted/50">
                                        <div
                                            class="space-y-2 px-4 py-2 text-sm"
                                        >
                                            <p class="font-medium">
                                                Components:
                                            </p>
                                            <div
                                                v-for="component in item.invoice_item_components"
                                                :key="component.id"
                                                class="grid grid-cols-5 gap-2 rounded border bg-background p-2"
                                            >
                                                <div>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Inventory Item
                                                    </p>
                                                    <p>
                                                        {{
                                                            component
                                                                .inventory_item
                                                                ?.name || 'N/A'
                                                        }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Warehouse
                                                    </p>
                                                    <p>
                                                        {{
                                                            component.warehouse
                                                                ?.name || 'N/A'
                                                        }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Qty
                                                    </p>
                                                    <p>{{ component.qty }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Share %
                                                    </p>
                                                    <p>
                                                        {{
                                                            component.share_percent
                                                        }}%
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Share Amount
                                                    </p>
                                                    <p class="font-medium">
                                                        {{
                                                            formatCurrency(
                                                                component.share_amount,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>

                    <!-- Totals -->
                    <div class="mt-4 flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Subtotal:</span>
                                <span>{{
                                    formatCurrency(invoice.subtotal)
                                }}</span>
                            </div>
                            <div
                                v-if="invoice.discount_amount > 0"
                                class="flex justify-between text-sm"
                            >
                                <span>Discount:</span>
                                <span
                                    >-{{
                                        formatCurrency(invoice.discount_amount)
                                    }}</span
                                >
                            </div>
                            <div
                                v-if="invoice.tax_amount > 0"
                                class="flex justify-between text-sm"
                            >
                                <span>Tax:</span>
                                <span>{{
                                    formatCurrency(invoice.tax_amount)
                                }}</span>
                            </div>
                            <div
                                v-if="invoice.shipping_fee > 0"
                                class="flex justify-between text-sm"
                            >
                                <span>Shipping:</span>
                                <span>{{
                                    formatCurrency(invoice.shipping_fee)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t pt-2 text-lg font-bold"
                            >
                                <span>Total:</span>
                                <span>{{
                                    formatCurrency(invoice.total_amount)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Revenue Shares by Owner -->
            <Card v-if="revenueSharesByOwner.length > 0">
                <CardHeader>
                    <CardTitle>Revenue Shares by Component Owner</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="share in revenueSharesByOwner"
                            :key="share.owner.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="font-semibold">
                                    {{ share.owner.name }}
                                </h4>
                                <p class="text-lg font-bold">
                                    {{ formatCurrency(share.totalShare) }}
                                </p>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Line Item</TableHead>
                                        <TableHead>Inventory Item</TableHead>
                                        <TableHead>Warehouse</TableHead>
                                        <TableHead class="text-right">
                                            Qty
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Share %
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(comp, idx) in share.components"
                                        :key="idx"
                                    >
                                        <TableCell>{{ comp.item }}</TableCell>
                                        <TableCell>{{
                                            comp.inventory_item
                                        }}</TableCell>
                                        <TableCell>{{
                                            comp.warehouse
                                        }}</TableCell>
                                        <TableCell class="text-right">{{
                                            comp.qty
                                        }}</TableCell>
                                        <TableCell class="text-right"
                                            >{{
                                                comp.share_percent
                                            }}%</TableCell
                                        >
                                        <TableCell class="text-right">{{
                                            formatCurrency(comp.share_amount)
                                        }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Notes and Terms -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card v-if="invoice.notes">
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">
                            {{ invoice.notes }}
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="invoice.terms">
                    <CardHeader>
                        <CardTitle>Terms & Conditions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">
                            {{ invoice.terms }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
