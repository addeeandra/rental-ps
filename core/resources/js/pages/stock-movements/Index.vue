<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { PaginatedData } from '@/types/laravel';
import type { InventoryItem, StockMovement, Warehouse } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import StockMovementFormDialog from './StockMovementFormDialog.vue';

interface Props {
    movements: PaginatedData<StockMovement>;
    inventoryItems: InventoryItem[];
    warehouses: Warehouse[];
    reasons: Record<string, string>;
    filters: {
        search?: string;
        inventory_item_id?: number;
        warehouse_id?: number;
        reason?: string;
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stock Movements',
        href: '/stock-movements',
    },
];

const search = ref(props.filters.search || '');
const selectedItemId = ref(props.filters.inventory_item_id?.toString() || '');
const selectedWarehouseId = ref(props.filters.warehouse_id?.toString() || '');
const selectedReason = ref(props.filters.reason || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const showCreateDialog = ref(false);

const { showToast } = useToast();
const page = usePage();

// Watch for flash messages
watch(
    () => [page.props.success, page.props.error, page.props.warning],
    ([success, error, warning]) => {
        if (success) {
            showToast(success as string, 'success');
        }
        if (error) {
            showToast(error as string, 'error');
        }
        if (warning) {
            showToast(warning as string, 'warning');
        }
    },
    { immediate: true },
);

function getFilters() {
    return {
        search: search.value,
        inventory_item_id: selectedItemId.value,
        warehouse_id: selectedWarehouseId.value,
        reason: selectedReason.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    };
}

// Debounced search
const debouncedSearch = debounce((value: string) => {
    router.get('/stock-movements', { ...getFilters(), search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

watch([selectedItemId, selectedWarehouseId, selectedReason, dateFrom, dateTo], () => {
    router.get('/stock-movements', getFilters(), {
        preserveState: true,
        replace: true,
    });
});

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getQuantityBadgeVariant(quantity: number): 'default' | 'destructive' {
    return quantity >= 0 ? 'default' : 'destructive';
}

function formatQuantity(quantity: number): string {
    const sign = quantity >= 0 ? '+' : '';
    return sign + quantity.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

function clearFilters() {
    search.value = '';
    selectedItemId.value = '';
    selectedWarehouseId.value = '';
    selectedReason.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/stock-movements', {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Stock Movements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Stock Movements</h1>
                    <p class="text-muted-foreground">
                        Track all stock changes and adjustments.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        New Movement
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                placeholder="Search notes..."
                                class="pl-9"
                            />
                        </div>
                        <select
                            v-model="selectedItemId"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Items</option>
                            <option
                                v-for="item in inventoryItems"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.sku }} - {{ item.name }}
                            </option>
                        </select>
                        <select
                            v-model="selectedWarehouseId"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Warehouses</option>
                            <option
                                v-for="warehouse in warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                            >
                                {{ warehouse.code }} - {{ warehouse.name }}
                            </option>
                        </select>
                        <select
                            v-model="selectedReason"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Reasons</option>
                            <option
                                v-for="(label, value) in reasons"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">From Date</label>
                            <Input
                                v-model="dateFrom"
                                type="date"
                                class="w-full sm:w-auto"
                            />
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">To Date</label>
                            <Input
                                v-model="dateTo"
                                type="date"
                                class="w-full sm:w-auto"
                            />
                        </div>
                        <Button variant="outline" size="sm" @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Movements Table -->
            <Card v-if="movements.data.length > 0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Item</TableHead>
                                <TableHead>Warehouse</TableHead>
                                <TableHead>Quantity</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead>By</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="movement in movements.data" :key="movement.id">
                                <TableCell class="whitespace-nowrap text-sm">
                                    {{ formatDate(movement.created_at) }}
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ movement.inventory_item?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ movement.inventory_item?.sku }}</div>
                                </TableCell>
                                <TableCell>
                                    {{ movement.warehouse?.name }}
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getQuantityBadgeVariant(movement.quantity)">
                                        {{ formatQuantity(movement.quantity) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ reasons[movement.reason] || movement.reason }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="max-w-[200px] truncate">
                                    {{ movement.notes || '-' }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ movement.created_by_user?.name || '-' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card
                v-else
                class="flex min-h-[400px] flex-col items-center justify-center"
            >
                <CardContent
                    class="flex flex-col items-center gap-4 py-8 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <ArrowLeftRight class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold">No movements found</h3>
                        <p class="text-muted-foreground">
                            {{
                                search || selectedItemId || selectedWarehouseId || selectedReason || dateFrom || dateTo
                                    ? 'Try adjusting your filters.'
                                    : 'Get started by creating your first stock movement.'
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="!search && !selectedItemId && !selectedWarehouseId && !selectedReason && !dateFrom && !dateTo"
                        @click="showCreateDialog = true"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Create Movement
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="movements.data.length > 0"
                class="flex items-center justify-end gap-2"
            >
                <Button
                    v-for="(link, index) in movements.links"
                    :key="index"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url && router.visit(link.url)"
                >
                    <span v-html="link.label" />
                </Button>
            </div>
        </div>

        <!-- Dialog -->
        <StockMovementFormDialog
            v-model:open="showCreateDialog"
            :inventory-items="inventoryItems"
            :warehouses="warehouses"
            :reasons="reasons"
            @success="showCreateDialog = false"
        />
    </AppLayout>
</template>
