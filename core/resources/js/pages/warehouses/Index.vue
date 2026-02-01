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
import type { Warehouse } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { MoreVertical, Plus, Search, Warehouse as WarehouseIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DeleteWarehouseDialog from './DeleteWarehouseDialog.vue';
import WarehouseFormDialog from './WarehouseFormDialog.vue';

interface Props {
    warehouses: PaginatedData<Warehouse>;
    filters: {
        search?: string;
        is_active?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Warehouses',
        href: '/warehouses',
    },
];

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.is_active || '');
const showCreateDialog = ref(false);
const editingWarehouse = ref<Warehouse | null>(null);
const deletingWarehouse = ref<Warehouse | null>(null);

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
        '/warehouses',
        { search: value, is_active: selectedStatus.value },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

watch(selectedStatus, (value) => {
    router.get(
        '/warehouses',
        { search: search.value, is_active: value },
        {
            preserveState: true,
            replace: true,
        },
    );
});

function handleEdit(warehouse: Warehouse) {
    editingWarehouse.value = warehouse;
}

function handleDelete(warehouse: Warehouse) {
    deletingWarehouse.value = warehouse;
}
</script>

<template>
    <Head title="Warehouses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Warehouses</h1>
                    <p class="text-muted-foreground">
                        Manage your storage locations.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        New Warehouse
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
                        placeholder="Search warehouses by name, code, or address..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="selectedStatus"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:w-[200px]"
                >
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <!-- Warehouses Grid -->
            <div
                v-if="warehouses.data.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="warehouse in warehouses.data"
                    :key="warehouse.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-1">
                                <CardDescription class="text-xs">{{
                                    warehouse.code
                                }}</CardDescription>
                                <CardTitle class="text-lg">{{
                                    warehouse.name
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
                                        @click="handleEdit(warehouse)"
                                    >
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="handleDelete(warehouse)"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <Badge :variant="warehouse.is_active ? 'default' : 'secondary'">
                            {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div
                            v-if="warehouse.address"
                            class="line-clamp-2 text-muted-foreground"
                        >
                            {{ warehouse.address }}
                        </div>
                        <div v-else class="text-muted-foreground italic">
                            No address
                        </div>
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-muted-foreground">Items with stock:</span>
                            <Badge variant="outline">{{ warehouse.total_items || 0 }}</Badge>
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
                        <WarehouseIcon class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold">No warehouses found</h3>
                        <p class="text-muted-foreground">
                            {{
                                search || selectedStatus
                                    ? 'Try adjusting your search or filters.'
                                    : 'Get started by creating your first warehouse.'
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="!search && !selectedStatus"
                        @click="showCreateDialog = true"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Create Warehouse
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="warehouses.data.length > 0"
                class="flex items-center justify-end gap-2"
            >
                <Button
                    v-for="(link, index) in warehouses.links"
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
        <WarehouseFormDialog
            v-model:open="showCreateDialog"
            @success="showCreateDialog = false"
        />

        <WarehouseFormDialog
            v-if="editingWarehouse"
            v-model:open="editingWarehouse"
            :warehouse="editingWarehouse"
            @success="editingWarehouse = null"
            @cancel="editingWarehouse = null"
        />

        <DeleteWarehouseDialog
            v-if="deletingWarehouse"
            v-model:open="deletingWarehouse"
            :warehouse="deletingWarehouse"
            @success="deletingWarehouse = null"
            @cancel="deletingWarehouse = null"
        />
    </AppLayout>
</template>
