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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Partner } from '@/types/models';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import DeletePartnerDialog from './DeletePartnerDialog.vue';
import PartnerFormDialog from './PartnerFormDialog.vue';

interface Props {
    partner: Partner;
    context?: 'customers' | 'suppliers';
}

const props = withDefaults(defineProps<Props>(), {
    context: 'customers',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: props.context === 'customers' ? 'Customers' : 'Suppliers',
        href: props.context === 'customers' ? '/customers' : '/suppliers',
    },
    {
        title: props.partner.name,
        href: `/${props.context}/${props.partner.id}`,
    },
];

const editingPartner = ref<Partner | null>(null);
const deletingPartner = ref<Partner | null>(null);

function handleEdit() {
    editingPartner.value = props.partner;
}

function handleDelete() {
    deletingPartner.value = props.partner;
}

function goBack() {
    router.visit(props.context === 'customers' ? '/customers' : '/suppliers');
}
</script>

<template>
    <Head :title="partner.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" @click="goBack">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-3xl font-bold">
                                {{ partner.name }}
                            </h1>
                            <Badge
                                v-if="partner.type === 'Supplier & Client'"
                                class="border-0 bg-gradient-to-r from-blue-500 to-purple-500 text-white"
                            >
                                {{ partner.type }}
                            </Badge>
                            <Badge v-else>
                                {{ partner.type }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground">{{ partner.code }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handleEdit">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                    <Button variant="destructive" @click="handleDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Content -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Contact Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Contact Information</CardTitle>
                        <CardDescription>Basic contact details</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="partner.email">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Email
                            </div>
                            <div class="mt-1">{{ partner.email }}</div>
                        </div>
                        <div v-if="partner.phone">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Phone
                            </div>
                            <div class="mt-1">{{ partner.phone }}</div>
                        </div>
                        <div v-if="partner.mobile_phone">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Mobile Phone
                            </div>
                            <div class="mt-1">{{ partner.mobile_phone }}</div>
                        </div>
                        <div v-if="partner.website">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Website
                            </div>
                            <div class="mt-1">
                                <a
                                    :href="partner.website"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                >
                                    {{ partner.website }}
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Address Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Address</CardTitle>
                        <CardDescription>Location details</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="
                                partner.address_line_1 || partner.address_line_2
                            "
                        >
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Street Address
                            </div>
                            <div class="mt-1">
                                <div v-if="partner.address_line_1">
                                    {{ partner.address_line_1 }}
                                </div>
                                <div v-if="partner.address_line_2">
                                    {{ partner.address_line_2 }}
                                </div>
                            </div>
                        </div>
                        <div v-if="partner.city || partner.province">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                City & Province
                            </div>
                            <div class="mt-1">
                                {{ partner.city
                                }}<span v-if="partner.province"
                                    >, {{ partner.province }}</span
                                >
                            </div>
                        </div>
                        <div v-if="partner.postal_code">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Postal Code
                            </div>
                            <div class="mt-1">{{ partner.postal_code }}</div>
                        </div>
                        <div v-if="partner.country">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Country
                            </div>
                            <div class="mt-1">{{ partner.country }}</div>
                        </div>
                        <div v-if="partner.gmap_url">
                            <div
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Google Maps
                            </div>
                            <div class="mt-1">
                                <a
                                    :href="partner.gmap_url"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                >
                                    View on Maps
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="partner.notes" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">
                            {{ partner.notes }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Dialogs -->
        <PartnerFormDialog
            v-if="editingPartner"
            v-model:open="editingPartner"
            :partner="editingPartner"
            :default-type="context === 'customers' ? 'Client' : 'Supplier'"
            :context="context"
            @success="editingPartner = null"
            @cancel="editingPartner = null"
        />

        <DeletePartnerDialog
            v-if="deletingPartner"
            v-model:open="deletingPartner"
            :partner="deletingPartner"
            :delete-route="
                context === 'customers'
                    ? 'customers.destroy'
                    : 'suppliers.destroy'
            "
            @success="goBack()"
            @cancel="deletingPartner = null"
        />
    </AppLayout>
</template>
