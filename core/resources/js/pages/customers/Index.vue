<script setup lang="ts">
import Icon from '@/components/Icon.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { debounce } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { PaginatedData } from '@/types/laravel';
import type { Partner } from '@/types/models';
import { Head, router } from '@inertiajs/vue3';
import { Download, MoreVertical, Plus, Search, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DeletePartnerDialog from '../partners/DeletePartnerDialog.vue';
import ImportPartnersDialog from '../partners/ImportPartnersDialog.vue';
import PartnerFormDialog from '../partners/PartnerFormDialog.vue';

interface Props {
    partners: PaginatedData<Partner>;
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: '/customers',
    },
];

const search = ref(props.filters.search || '');
const showCreateDialog = ref(false);
const showImportDialog = ref(false);
const editingPartner = ref<Partner | null>(null);
const deletingPartner = ref<Partner | null>(null);

// Debounced search
const debouncedSearch = debounce((value: string) => {
    router.get(
        '/customers',
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

function getTypeBadgeVariant(
    type: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (type === 'Supplier & Client') {
        return 'default';
    }
    return 'default';
}

function handleEdit(partner: Partner) {
    editingPartner.value = partner;
}

function handleDelete(partner: Partner) {
    deletingPartner.value = partner;
}

function downloadTemplate() {
    window.location.href = '/customers/template';
}
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold">Customers</h1>
                    <p class="text-muted-foreground">
                        Manage your customers and clients.
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
                        New Customer
                    </Button>
                </div>
            </div>

            <!-- Search -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search customers by name, email, code, or phone..."
                        class="pl-9"
                    />
                </div>
            </div>

            <!-- Customers Grid -->
            <div
                v-if="partners.data.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="partner in partners.data"
                    :key="partner.id"
                    class="relative"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 space-y-1">
                                <CardDescription class="text-xs">{{
                                    partner.code
                                }}</CardDescription>
                                <CardTitle class="text-lg">{{
                                    partner.name
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
                                        @click="handleEdit(partner)"
                                    >
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="handleDelete(partner)"
                                    >
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <Badge
                            v-if="partner.type === 'Supplier & Client'"
                            class="border-0 bg-gradient-to-r from-blue-500 to-purple-500 text-white"
                        >
                            {{ partner.type }}
                        </Badge>
                        <Badge
                            v-else
                            :variant="getTypeBadgeVariant(partner.type)"
                        >
                            {{ partner.type }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div
                            v-if="partner.email"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Icon name="Mail" color="#a0a0a0" />
                            <span class="truncate">{{ partner.email }}</span>
                        </div>
                        <div
                            v-if="partner.phone || partner.mobile_phone"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Icon name="Smartphone" color="#a0a0a0" />
                            <span>{{
                                partner.mobile_phone || partner.phone
                            }}</span>
                        </div>
                        <div
                            v-if="partner.city"
                            class="flex items-center gap-2 text-muted-foreground"
                        >
                            <Icon name="MapPinHouse" color="#a0a0a0" />
                            <span
                                >{{ partner.city
                                }}<span v-if="partner.province"
                                    >, {{ partner.province }}</span
                                ></span
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
                            No customers found
                        </h3>
                        <p class="text-muted-foreground">
                            {{
                                search
                                    ? 'Try adjusting your search.'
                                    : 'Get started by creating your first customer.'
                            }}
                        </p>
                    </div>
                    <Button v-if="!search" @click="showCreateDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Customer
                    </Button>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="partners.data.length > 0"
                class="hidden items-center justify-end gap-2 sm:flex"
            >
                <Button
                    v-for="(link, index) in partners.links"
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
                v-if="partners.data.length > 0"
                class="flex items-center justify-end gap-2 sm:hidden"
            >
                <Button
                    :key="0"
                    :variant="partners.links[0].active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!partners.links[0].url"
                    @click="
                        partners.links[0].url &&
                        router.visit(partners.links[0].url)
                    "
                >
                    <span v-html="partners.links[0].label" />
                </Button>
                <Button
                    :key="partners.links.length - 1"
                    :variant="
                        partners.links[partners.links.length - 1].active
                            ? 'default'
                            : 'outline'
                    "
                    size="sm"
                    :disabled="!partners.links[partners.links.length - 1].url"
                    @click="
                        () => {
                            let url =
                                partners.links[partners.links.length - 1].url;

                            if (url !== null) {
                                router.visit(url);
                            }
                        }
                    "
                >
                    <span
                        v-html="partners.links[partners.links.length - 1].label"
                    />
                </Button>
            </div>
        </div>

        <!-- Dialogs -->
        <PartnerFormDialog
            v-model:open="showCreateDialog"
            default-type="Client"
            context="customers"
            @success="showCreateDialog = false"
        />

        <PartnerFormDialog
            v-if="editingPartner"
            v-model:open="editingPartner"
            :partner="editingPartner"
            default-type="Client"
            context="customers"
            @success="editingPartner = null"
            @cancel="editingPartner = null"
        />

        <ImportPartnersDialog
            v-model:open="showImportDialog"
            import-url="/customers/import"
            @success="showImportDialog = false"
        />

        <DeletePartnerDialog
            v-if="deletingPartner"
            v-model:open="deletingPartner"
            :partner="deletingPartner"
            delete-route="customers.destroy"
            @success="deletingPartner = null"
            @cancel="deletingPartner = null"
        />
    </AppLayout>
</template>
