<script setup lang="ts">
import Icon from '@/components/Icon.vue';
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
import type { Category } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Download, MoreVertical, Plus, Search, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import CategoryFormDialog from './CategoryFormDialog.vue';
import DeleteCategoryDialog from './DeleteCategoryDialog.vue';
import ImportCategoriesDialog from './ImportCategoriesDialog.vue';

interface Props {
    categories: PaginatedData<Category>;
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

const search = ref(props.filters.search || '');
const showCreateDialog = ref(false);
const showImportDialog = ref(false);
const editingCategory = ref<Category | null>(null);
const deletingCategory = ref<Category | null>(null);

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
        '/categories',
        { search: value },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(search, (value) => {
    debouncedSearch(value);
});

function handleEdit(category: Category) {
    editingCategory.value = category;
}

function handleDelete(category: Category) {
    deletingCategory.value = category;
}

function downloadTemplate() {
    window.location.href = '/categories/template';
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Categories</h1>
                    <p class="text-muted-foreground">
                        Manage product categories and classifications.
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
                        New Category
                    </Button>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search categories by name, code, or description..."
                    class="pl-9"
                />
            </div>

            <!-- Categories Grid -->
            <div
                v-if="categories.data.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="category in categories.data"
                    :key="category.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-1">
                                <CardDescription class="text-xs">{{
                                    category.code
                                }}</CardDescription>
                                <CardTitle class="text-lg">{{
                                    category.name
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
                                        @click="handleEdit(category)"
                                    >
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="handleDelete(category)"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div
                            v-if="category.description"
                            class="line-clamp-2 text-muted-foreground"
                        >
                            {{ category.description }}
                        </div>
                        <div
                            v-if="category.products_count !== undefined"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Icon name="Package" color="#a0a0a0" />
                            <span
                                >{{ category.products_count }} product{{
                                    category.products_count !== 1 ? 's' : ''
                                }}</span
                            >
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
                        <h3 class="text-xl font-semibold">
                            No categories found
                        </h3>
                        <p class="text-muted-foreground">
                            {{
                                search
                                    ? 'Try adjusting your search.'
                                    : 'Get started by creating your first category.'
                            }}
                        </p>
                    </div>
                    <Button v-if="!search" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Category
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="categories.data.length > 0"
                class="hidden items-center justify-end gap-2 sm:flex"
            >
                <Button
                    v-for="(link, index) in categories.links"
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
                v-if="categories.data.length > 0"
                class="flex items-center justify-end gap-2 sm:hidden"
            >
                <Button
                    :key="0"
                    :variant="
                        categories.links[0].active ? 'default' : 'outline'
                    "
                    size="sm"
                    :disabled="!categories.links[0].url"
                    @click="
                        categories.links[0].url &&
                        router.visit(categories.links[0].url)
                    "
                >
                    <span v-html="categories.links[0].label" />
                </Button>
                <Button
                    :key="categories.links.length - 1"
                    :variant="
                        categories.links[categories.links.length - 1].active
                            ? 'default'
                            : 'outline'
                    "
                    size="sm"
                    :disabled="
                        !categories.links[categories.links.length - 1].url
                    "
                    @click="
                        () => {
                            let url =
                                categories.links[categories.links.length - 1]
                                    .url;

                            if (url !== null) {
                                router.visit(url);
                            }
                        }
                    "
                >
                    <span
                        v-html="
                            categories.links[categories.links.length - 1].label
                        "
                    />
                </Button>
            </div>
        </div>

        <!-- Dialogs -->
        <CategoryFormDialog
            v-model:open="showCreateDialog"
            @success="showCreateDialog = false"
        />

        <CategoryFormDialog
            v-if="editingCategory"
            v-model:open="editingCategory"
            :category="editingCategory"
            @success="editingCategory = null"
            @cancel="editingCategory = null"
        />

        <ImportCategoriesDialog
            v-model:open="showImportDialog"
            @success="showImportDialog = false"
        />

        <DeleteCategoryDialog
            v-if="deletingCategory"
            v-model:open="deletingCategory"
            :category="deletingCategory"
            @success="deletingCategory = null"
            @cancel="deletingCategory = null"
        />
    </AppLayout>
</template>
