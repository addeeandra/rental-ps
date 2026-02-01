<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { PaginatedData } from '@/types/laravel';
import type { InventoryItem, Partner } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Box, MoreVertical, Plus, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DeleteInventoryItemDialog from './DeleteInventoryItemDialog.vue';
import InventoryItemFormDialog from './InventoryItemFormDialog.vue';

interface Props {
    inventoryItems: PaginatedData<InventoryItem>;
    suppliers: Partner[];
    filters: {
        search?: string;
        owner_id?: number;
        is_active?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory Items',
        href: '/inventory-items',
    },
];

const search = ref(props.filters.search || '');
const selectedOwnerId = ref(props.filters.owner_id?.toString() || '');
const selectedStatus = ref(props.filters.is_active || '');
const showCreateDialog = ref(false);
const editingItem = ref<InventoryItem | null>(null);
const deletingItem = ref<InventoryItem | null>(null);

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

// Debounced search
const debouncedSearch = debounce((value: string) => {
    router.get(
        '/inventory-items',
        { search: value, owner_id: selectedOwnerId.value, is_active: selectedStatus.value },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

watch(selectedOwnerId, (value) => {
    router.get(
        '/inventory-items',
        { search: search.value, owner_id: value, is_active: selectedStatus.value },
        {
            preserveState: true,
            replace: true,
        },
    );
});

watch(selectedStatus, (value) => {
    router.get(
        '/inventory-items',
        { search: search.value, owner_id: selectedOwnerId.value, is_active: value },
        {
            preserveState: true,
            replace: true,
        },
    );
});

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID').format(Math.floor(price));
}

function handleEdit(item: InventoryItem) {
    editingItem.value = item;
}

function handleDelete(item: InventoryItem) {
    deletingItem.value = item;
}

function getStockBadgeVariant(stock: number | undefined): 'default' | 'destructive' | 'secondary' {
    if (stock === undefined || stock === 0) return 'secondary';
    if (stock < 0) return 'destructive';
    return 'default';
}
</script>

<template>
    <Head title="Inventory Items" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Inventory Items</h1>
                    <p class="text-muted-foreground">
                        Manage your inventory items with ownership tracking.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        New Item
                    </Button>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search by name, SKU, or owner..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="selectedOwnerId"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:w-[200px]"
                >
                    <option value="">All Owners</option>
                    <option
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        :value="supplier.id"
                    >
                        {{ supplier.name }}
                    </option>
                </select>
                <select
                    v-model="selectedStatus"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:w-[150px]"
                >
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <!-- Items Grid -->
            <div
                v-if="inventoryItems.data.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="item in inventoryItems.data"
                    :key="item.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-1">
                                <CardDescription class="text-xs">{{
                                    item.sku
                                }}</CardDescription>
                                <CardTitle class="text-lg">{{
                                    item.name
                                }}</CardTitle>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                    >
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        @click="handleEdit(item)"
                                    >
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="handleDelete(item)"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div class="flex gap-2">
                            <Badge :variant="item.is_active ? 'default' : 'secondary'">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                            <Badge :variant="getStockBadgeVariant(item.total_stock)">
                                Stock: {{ item.total_stock ?? 0 }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Owner:</span>
                                <span class="font-medium">{{ item.owner?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Cost:</span>
                                <span class="font-semibold">Rp {{ formatPrice(item.cost) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Share %:</span>
                                <span>{{ item.default_share_percent }}%</span>
                            </div>
                            <div v-if="item.unit" class="flex items-center justify-between">
                                <span class="text-muted-foreground">Unit:</span>
                                <span>{{ item.unit }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card
                v-else
                class="flex min-h-[400px] flex-col items-center justify-center"
            >
                <CardContent
                    class="flex flex-col items-center gap-4 py-8 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <Box class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold">No inventory items found</h3>
                        <p class="text-muted-foreground">
                            {{
                                search || selectedOwnerId || selectedStatus
                                    ? 'Try adjusting your search or filters.'
                                    : 'Get started by creating your first inventory item.'
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="!search && !selectedOwnerId && !selectedStatus"
                        @click="showCreateDialog = true"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Create Item
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="inventoryItems.data.length > 0"
                class="flex items-center justify-end gap-2"
            >
                <Button
                    v-for="(link, index) in inventoryItems.links"
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

        <!-- Dialogs -->
        <InventoryItemFormDialog
            v-model:open="showCreateDialog"
            :suppliers="suppliers"
            @success="showCreateDialog = false"
        />

        <InventoryItemFormDialog
            v-if="editingItem"
            v-model:open="editingItem"
            :item="editingItem"
            :suppliers="suppliers"
            @success="editingItem = null"
            @cancel="editingItem = null"
        />

        <DeleteInventoryItemDialog
            v-if="deletingItem"
            v-model:open="deletingItem"
            :item="deletingItem"
            @success="deletingItem = null"
            @cancel="deletingItem = null"
        />
    </AppLayout>
</template>
