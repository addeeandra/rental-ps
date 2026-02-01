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
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { PaginatedData } from '@/types/laravel';
import type { InventoryItem, StockLevel, Warehouse } from '@/types/models';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    stockLevels: PaginatedData<StockLevel>;
    inventoryItems: InventoryItem[];
    warehouses: Warehouse[];
    filters: {
        search?: string;
        inventory_item_id?: number;
        warehouse_id?: number;
        status?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stock Levels',
        href: '/stock-levels',
    },
];

const search = ref(props.filters.search || '');
const selectedItemId = ref(props.filters.inventory_item_id?.toString() || '');
const selectedWarehouseId = ref(props.filters.warehouse_id?.toString() || '');
const selectedStatus = ref(props.filters.status || '');

function getFilters() {
    return {
        search: search.value,
        inventory_item_id: selectedItemId.value,
        warehouse_id: selectedWarehouseId.value,
        status: selectedStatus.value,
    };
}

// Debounced search
const debouncedSearch = debounce((value: string) => {
    router.get('/stock-levels', { ...getFilters(), search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

watch([selectedItemId, selectedWarehouseId, selectedStatus], () => {
    router.get('/stock-levels', getFilters(), {
        preserveState: true,
        replace: true,
    });
});

function getStockBadgeVariant(level: StockLevel): 'default' | 'destructive' | 'secondary' | 'outline' {
    if (level.qty_on_hand < 0) return 'destructive';
    if (level.qty_on_hand < level.min_threshold) return 'secondary';
    return 'default';
}

function getStockStatus(level: StockLevel): string {
    if (level.qty_on_hand < 0) return 'Negative';
    if (level.qty_on_hand < level.min_threshold) return 'Low';
    return 'OK';
}

function formatQuantity(qty: number): string {
    return qty.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

function clearFilters() {
    search.value = '';
    selectedItemId.value = '';
    selectedWarehouseId.value = '';
    selectedStatus.value = '';
    router.get('/stock-levels', {}, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Stock Levels" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Stock Levels</h1>
                    <p class="text-muted-foreground">
                        View current stock levels across all warehouses.
                    </p>
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
                                placeholder="Search..."
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
                            v-model="selectedStatus"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">All Status</option>
                            <option value="negative">Negative</option>
                            <option value="low">Low Stock</option>
                            <option value="ok">OK</option>
                        </select>
                    </div>
                    <div class="mt-4">
                        <Button variant="outline" size="sm" @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Stock Levels Table -->
            <Card v-if="stockLevels.data.length > 0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead>Owner</TableHead>
                                <TableHead>Warehouse</TableHead>
                                <TableHead class="text-right">On Hand</TableHead>
                                <TableHead class="text-right">Reserved</TableHead>
                                <TableHead class="text-right">Available</TableHead>
                                <TableHead class="text-right">Min Threshold</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="level in stockLevels.data" :key="`${level.inventory_item_id}-${level.warehouse_id}`">
                                <TableCell>
                                    <div class="font-medium">{{ level.inventory_item?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ level.inventory_item?.sku }}</div>
                                </TableCell>
                                <TableCell>
                                    {{ level.inventory_item?.owner?.name || '-' }}
                                </TableCell>
                                <TableCell>
                                    <div>{{ level.warehouse?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ level.warehouse?.code }}</div>
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    <span :class="{ 'text-destructive': level.qty_on_hand < 0 }">
                                        {{ formatQuantity(level.qty_on_hand) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatQuantity(level.qty_reserved) }}
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatQuantity(level.qty_on_hand - level.qty_reserved) }}
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatQuantity(level.min_threshold) }}
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStockBadgeVariant(level)">
                                        {{ getStockStatus(level) }}
                                    </Badge>
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
                        <ClipboardList class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold">No stock levels found</h3>
                        <p class="text-muted-foreground">
                            {{
                                search || selectedItemId || selectedWarehouseId || selectedStatus
                                    ? 'Try adjusting your filters.'
                                    : 'Stock levels will appear here after recording stock movements.'
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="stockLevels.data.length > 0"
                class="flex items-center justify-end gap-2"
            >
                <Button
                    v-for="(link, index) in stockLevels.links"
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
    </AppLayout>
</template>
