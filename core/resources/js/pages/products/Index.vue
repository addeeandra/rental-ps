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
import type { Category, InventoryItem, Product } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Download, MoreVertical, Plus, Search, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DeleteProductDialog from './DeleteProductDialog.vue';
import ImportProductsDialog from './ImportProductsDialog.vue';
import ProductFormDialog from './ProductFormDialog.vue';

interface Props {
    products: PaginatedData<Product>;
    categories: Category[];
    inventoryItems: InventoryItem[];
    filters: {
        search?: string;
        category_id?: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

const search = ref(props.filters.search || '');
const selectedCategoryId = ref(props.filters.category_id?.toString() || '');
const showCreateDialog = ref(false);
const showImportDialog = ref(false);
const editingProduct = ref<Product | null>(null);
const deletingProduct = ref<Product | null>(null);

const { showToast } = useToast();
const page = usePage();

// Watch for flash messages
watch(
    () => [page.props.success, page.props.error],
    ([success, error]) => {
        if (success) {
            showToast(success as string, 'success');
        }
        if (error) {
            showToast(error as string, 'error');
        }
    },
    { immediate: true },
);

// Debounced search
const debouncedSearch = debounce((value: string) => {
    router.get(
        '/products',
        { search: value, category_id: selectedCategoryId.value },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

watch(selectedCategoryId, (value) => {
    router.get(
        '/products',
        { search: search.value, category_id: value },
        {
            preserveState: true,
            replace: true,
        },
    );
});

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID').format(Math.floor(price));
}

function handleEdit(product: Product) {
    editingProduct.value = product;
}

function handleDelete(product: Product) {
    deletingProduct.value = product;
}

function downloadTemplate() {
    window.location.href = '/products/template';
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Products</h1>
                    <p class="text-muted-foreground">
                        Manage your rental products and pricing.
                    </p>
                </div>
                <div class="flex flex-col gap-2 md:flex-row">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="downloadTemplate"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        Template
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showImportDialog = true"
                    >
                        <Upload class="mr-2 h-4 w-4" />
                        Import
                    </Button>
                    <Button size="sm" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        New Product
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
                        placeholder="Search products by name, code, or description..."
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="selectedCategoryId"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:w-[200px]"
                >
                    <option value="">All Categories</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <!-- Products Grid -->
            <div
                v-if="products.data.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="product in products.data"
                    :key="product.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-1">
                                <CardDescription class="text-xs">{{
                                    product.code
                                }}</CardDescription>
                                <CardTitle class="text-lg">{{
                                    product.name
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
                                        @click="handleEdit(product)"
                                    >
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="handleDelete(product)"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <Badge v-if="product.category" variant="secondary">
                            {{ product.category.name }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div
                            v-if="product.description"
                            class="line-clamp-2 text-muted-foreground"
                        >
                            {{ product.description }}
                        </div>
                        <div class="space-y-1">
                            <div
                                v-if="product.sales_price > 0"
                                class="flex items-center justify-between"
                            >
                                <span class="text-muted-foreground"
                                    >Sales:</span
                                >
                                <span class="font-semibold"
                                    >Rp
                                    {{ formatPrice(product.sales_price) }}</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground"
                                    >Rental:</span
                                >
                                <span class="font-semibold"
                                    >Rp
                                    {{ formatPrice(product.rental_price) }}/{{
                                        product.rental_duration
                                    }}</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">UOM:</span>
                                <span>{{ product.uom }}</span>
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
                        <Search class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold">No products found</h3>
                        <p class="text-muted-foreground">
                            {{
                                search || selectedCategoryId
                                    ? 'Try adjusting your search or filters.'
                                    : 'Get started by creating your first product.'
                            }}
                        </p>
                    </div>
                    <Button
                        v-if="!search && !selectedCategoryId"
                        @click="showCreateDialog = true"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Create Product
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="products.data.length > 0"
                class="hidden items-center justify-end gap-2 sm:flex"
            >
                <Button
                    v-for="(link, index) in products.links"
                    :key="index"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url && router.visit(link.url)"
                >
                    <span v-html="link.label" />
                </Button>
            </div>

            <!-- Mobile Pagination -->
            <div
                v-if="products.data.length > 0"
                class="flex items-center justify-end gap-2 sm:hidden"
            >
                <Button
                    :key="0"
                    :variant="products.links[0].active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!products.links[0].url"
                    @click="
                        products.links[0].url &&
                        router.visit(products.links[0].url)
                    "
                >
                    <span v-html="products.links[0].label" />
                </Button>
                <Button
                    :key="products.links.length - 1"
                    :variant="
                        products.links[products.links.length - 1].active
                            ? 'default'
                            : 'outline'
                    "
                    size="sm"
                    :disabled="!products.links[products.links.length - 1].url"
                    @click="
                        () => {
                            let url =
                                products.links[products.links.length - 1].url;

                            if (url !== null) {
                                router.visit(url);
                            }
                        }
                    "
                >
                    <span
                        v-html="products.links[products.links.length - 1].label"
                    />
                </Button>
            </div>
        </div>

        <!-- Dialogs -->
        <ProductFormDialog
            v-model:open="showCreateDialog"
            :categories="categories"
            :inventory-items="inventoryItems"
            @success="showCreateDialog = false"
        />

        <ProductFormDialog
            v-if="editingProduct"
            v-model:open="editingProduct"
            :product="editingProduct"
            :categories="categories"
            :inventory-items="inventoryItems"
            @success="editingProduct = null"
            @cancel="editingProduct = null"
        />

        <ImportProductsDialog
            v-model:open="showImportDialog"
            @success="showImportDialog = false"
        />

        <DeleteProductDialog
            v-if="deletingProduct"
            v-model:open="deletingProduct"
            :product="deletingProduct"
            @success="deletingProduct = null"
            @cancel="deletingProduct = null"
        />
    </AppLayout>
</template>
