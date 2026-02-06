<script setup lang="ts">
import DeleteConfirmDialog from '@/components/invoices/DeleteConfirmDialog.vue';
import InvoiceFormDialog from '@/components/invoices/InvoiceFormDialog.vue';
import UpdatePaymentDialog from '@/components/invoices/UpdatePaymentDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCurrency, formatDate } from '@/composables/useFormatters';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import type { PaginatedData } from '@/types/laravel';
import type {
    InventoryItem,
    Invoice,
    InvoiceStatus,
    Partner,
    Product,
    Warehouse,
} from '@/types/models';
import { Head, router } from '@inertiajs/vue3';
import { FileText, MoreVertical, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    invoices: PaginatedData<Invoice>;
    partners: Partner[];
    products: Product[];
    warehouses: Warehouse[];
    inventoryItems: InventoryItem[];
    defaultInvoiceTerms?: string | null;
    defaultInvoiceNotes?: string | null;
    filters: {
        search?: string;
        status?: InvoiceStatus;
        partner_id?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPartnerId = ref(props.filters.partner_id || '');
const showCreateDialog = ref(false);
const paymentInvoice = ref<Invoice | null>(null);
const deletingInvoice = ref<Invoice | null>(null);

const breadcrumbs = [{ label: 'Invoices', href: '/invoices' }];

// Debounced search
const debouncedSearch = debounce(() => {
    router.get(
        '/invoices',
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            partner_id: selectedPartnerId.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch([search, selectedStatus, selectedPartnerId], () => {
    debouncedSearch();
});

function getStatusVariant(
    status: InvoiceStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'paid':
            return 'default'; // Green
        case 'partial':
            return 'secondary'; // Blue
        case 'unpaid':
            return 'outline'; // Yellow/Warning
        case 'void':
            return 'destructive'; // Red/Gray
        default:
            return 'outline';
    }
}

function getStatusLabel(status: InvoiceStatus): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function openPreviewPdf(invoice: Invoice) {
    window.open(`/invoices/${invoice.id}/preview`, '_blank');
}

function openPreviewHtml(invoice: Invoice) {
    window.open(`/invoices/${invoice.id}/preview-html`, '_blank');
}

function openInvoiceDetail(invoice: Invoice) {
    router.visit(`/invoices/${invoice.id}`);
}
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto space-y-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Invoices</h1>
                    <p class="text-muted-foreground">
                        Manage sales and rental invoices, track payments
                    </p>
                </div>
                <Button @click="showCreateDialog = true">
                    <Plus class="mr-2 h-4 w-4" />
                    New Invoice
                </Button>
            </div>

            <!-- Search and Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Search & Filter</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                placeholder="Search by invoice number, reference, or customer..."
                                class="pl-10"
                            />
                        </div>
                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Filter by status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All Statuses</SelectItem
                                >
                                <SelectItem value="unpaid">Unpaid</SelectItem>
                                <SelectItem value="partial">Partial</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="void">Void</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="selectedPartnerId">
                            <SelectTrigger>
                                <SelectValue placeholder="Filter by customer" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All Customers</SelectItem
                                >
                                <SelectItem
                                    v-for="partner in partners"
                                    :key="partner.id"
                                    :value="String(partner.id)"
                                >
                                    {{ partner.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Invoices List -->
            <div class="space-y-4">
                <Card v-for="invoice in invoices.data" :key="invoice.id">
                    <CardContent>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-3">
                                    <FileText
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                    <div>
                                        <h3 class="font-semibold">
                                            {{ invoice.invoice_number }}
                                        </h3>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{
                                                invoice.partner?.name ||
                                                'Unknown Customer'
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid gap-2 text-sm md:grid-cols-4">
                                    <div>
                                        <span class="text-muted-foreground"
                                            >Date:</span
                                        >
                                        <span class="ml-2">{{
                                            formatDate(invoice.invoice_date)
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground"
                                            >Due:</span
                                        >
                                        <span class="ml-2">{{
                                            formatDate(invoice.due_date)
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground"
                                            >Total:</span
                                        >
                                        <span class="ml-2 font-medium">{{
                                            formatCurrency(invoice.total_amount)
                                        }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground"
                                            >Balance:</span
                                        >
                                        <span class="ml-2 font-medium">{{
                                            formatCurrency(invoice.balance)
                                        }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :variant="
                                            getStatusVariant(invoice.status)
                                        "
                                    >
                                        {{ getStatusLabel(invoice.status) }}
                                    </Badge>
                                    <Badge variant="outline">
                                        {{
                                            invoice.order_type === 'sales'
                                                ? 'Sales'
                                                : 'Rental'
                                        }}
                                    </Badge>
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        v-if="invoice.is_editable"
                                        @click="
                                            router.visit(
                                                `/invoices/${invoice.id}/edit`,
                                            )
                                        "
                                    >
                                        Edit Invoice
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-else
                                        disabled
                                        class="text-muted-foreground"
                                    >
                                        Edit Invoice (Locked)
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="openInvoiceDetail(invoice)"
                                    >
                                        View Invoice
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="paymentInvoice = invoice"
                                    >
                                        Update Payment
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="openPreviewPdf(invoice)"
                                    >
                                        Preview PDF
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="openPreviewHtml(invoice)"
                                    >
                                        Preview HTML (Debug)
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="invoice.is_editable"
                                        class="text-destructive"
                                        @click="deletingInvoice = invoice"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardContent>
                </Card>

                <!-- Empty State -->
                <Card v-if="!invoices.data.length">
                    <CardContent
                        class="flex flex-col items-center justify-center py-12"
                    >
                        <FileText class="h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-semibold">
                            No invoices found
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Get started by creating your first invoice.
                        </p>
                        <Button class="mt-4" @click="showCreateDialog = true">
                            <Plus class="mr-2 h-4 w-4" />
                            New Invoice
                        </Button>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div
                    v-if="invoices.data.length"
                    class="flex items-center justify-between"
                >
                    <div class="text-sm text-muted-foreground">
                        Showing {{ invoices.from }} to {{ invoices.to }} of
                        {{ invoices.total }} invoices
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-for="link in invoices.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url)"
                        >
                            <span v-html="link.label" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialogs -->
        <InvoiceFormDialog
            v-model:open="showCreateDialog"
            :partners="partners"
            :products="products"
            :warehouses="warehouses"
            :inventory-items="inventoryItems"
            :default-invoice-terms="defaultInvoiceTerms"
            :default-invoice-notes="defaultInvoiceNotes"
            @success="showCreateDialog = false"
            @cancel="showCreateDialog = false"
        />

        <UpdatePaymentDialog
            v-if="paymentInvoice"
            v-model:open="paymentInvoice"
            :invoice="paymentInvoice"
            @success="paymentInvoice = null"
            @cancel="paymentInvoice = null"
        />

        <DeleteConfirmDialog
            v-if="deletingInvoice"
            v-model:open="deletingInvoice"
            :invoice="deletingInvoice"
            @success="deletingInvoice = null"
            @cancel="deletingInvoice = null"
        />
    </AppLayout>
</template>
