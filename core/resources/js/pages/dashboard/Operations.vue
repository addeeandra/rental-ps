<script setup lang="ts">
import EmptyState from '@/components/dashboard/EmptyState.vue';
import OverdueInvoicesDialog from '@/components/dashboard/OverdueInvoicesDialog.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/composables/useFormatters';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    Clock,
    PackageSearch,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    activeTab: string;
    sharedData: {
        activeRentalsCount: number;
    };
    overdueInvoices: Array<{
        id: number;
        invoice_number: string;
        partner_name: string;
        total_amount: number;
        balance: number;
        due_date: string;
        days_overdue: number;
        status: string;
    }>;
    activeRentals: Array<{
        id: number;
        invoice_number: string;
        partner_name: string;
        products: string;
        rental_start_date: string;
        rental_end_date: string;
        rental_days: number;
        days_remaining: number;
    }>;
    upcomingReturns: Array<{
        id: number;
        invoice_number: string;
        partner_name: string;
        products: string;
        rental_end_date: string;
        days_remaining: number;
    }>;
    overdueCount: number;
    overdueTotal: number;
    hasData: boolean;
}

defineProps<Props>();

const showOverdueDialog = ref(false);

function getDaysRemainingBadgeVariant(
    days: number,
): 'default' | 'secondary' | 'destructive' {
    if (days < 3) return 'destructive';
    if (days < 7) return 'secondary';
    return 'default';
}

function viewInvoice(id: number) {
    router.visit(`/invoices/${id}`);
}

function createInvoice() {
    router.visit('/invoices', { data: { create: true } });
}
</script>

<template>
    <Head title="Operations Dashboard" />

    <DashboardLayout
        :active-tab="activeTab as 'overview' | 'financial' | 'operations'"
    >
        <template v-if="!hasData">
            <EmptyState
                :icon="PackageSearch"
                title="No operations data yet"
                description="Create invoices and manage rentals to track your operational metrics."
                :primary-action="{
                    label: 'Create Your First Invoice',
                    onClick: createInvoice,
                }"
            />
        </template>

        <template v-else>
            <!-- Overdue Summary Card -->
            <Card v-if="overdueCount > 0" class="border-destructive">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <CardTitle class="flex items-center gap-2">
                                <AlertCircle class="h-5 w-5 text-destructive" />
                                Overdue Invoices
                            </CardTitle>
                            <CardDescription>
                                {{ overdueCount }} invoice{{
                                    overdueCount !== 1 ? 's' : ''
                                }}
                                overdue
                            </CardDescription>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-destructive">
                                {{ formatCurrency(overdueTotal) }}
                            </p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Button
                        variant="destructive"
                        class="w-full"
                        @click="showOverdueDialog = true"
                    >
                        View All Overdue Invoices
                    </Button>
                </CardContent>
            </Card>

            <!-- Upcoming Returns Alert -->
            <Alert
                v-if="upcomingReturns.length > 0"
                class="border-amber-500 bg-amber-50"
            >
                <AlertTriangle class="h-4 w-4 text-amber-600" />
                <AlertTitle class="text-amber-900">Upcoming Returns</AlertTitle>
                <AlertDescription class="text-amber-800">
                    {{ upcomingReturns.length }} rental{{
                        upcomingReturns.length !== 1 ? 's' : ''
                    }}
                    due back within 3 days
                </AlertDescription>
            </Alert>

            <!-- Main Content Grid -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Active Rentals -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Active Rentals</CardTitle>
                        <CardDescription
                            >Current equipment rentals in
                            progress</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <template v-if="activeRentals.length === 0">
                            <div
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No active rentals at the moment
                            </div>
                        </template>
                        <template v-else>
                            <div class="space-y-2">
                                <div
                                    v-for="rental in activeRentals"
                                    :key="rental.id"
                                    class="flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                    @click="viewInvoice(rental.id)"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">
                                            {{ rental.invoice_number }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ rental.partner_name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    rental.rental_start_date,
                                                )
                                            }}
                                            -
                                            {{
                                                formatDate(
                                                    rental.rental_end_date,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="space-y-1 text-right">
                                        <Badge
                                            :variant="
                                                getDaysRemainingBadgeVariant(
                                                    rental.days_remaining,
                                                )
                                            "
                                        >
                                            {{ rental.days_remaining }} day{{
                                                rental.days_remaining !== 1
                                                    ? 's'
                                                    : ''
                                            }}
                                            left
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Upcoming Returns Detail -->
                <Card v-if="upcomingReturns.length > 0" class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Upcoming Returns (Next 7 Days)</CardTitle>
                        <CardDescription
                            >Equipment due back soon - prepare for
                            inspection</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div
                                v-for="return_item in upcomingReturns"
                                :key="return_item.id"
                                class="flex cursor-pointer items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-3 transition-colors hover:bg-amber-100"
                                @click="viewInvoice(return_item.id)"
                            >
                                <div class="space-y-1">
                                    <p
                                        class="text-sm font-medium text-amber-900"
                                    >
                                        {{ return_item.invoice_number }}
                                    </p>
                                    <p class="text-xs text-amber-700">
                                        {{ return_item.partner_name }}
                                    </p>
                                </div>
                                <div class="space-y-1 text-right">
                                    <p
                                        class="text-sm font-medium text-amber-900"
                                    >
                                        {{
                                            formatDate(
                                                return_item.rental_end_date,
                                            )
                                        }}
                                    </p>
                                    <Badge
                                        variant="secondary"
                                        class="bg-amber-200 text-amber-900"
                                    >
                                        <Clock class="mr-1 h-3 w-3" />
                                        {{ return_item.days_remaining }} day{{
                                            return_item.days_remaining !== 1
                                                ? 's'
                                                : ''
                                        }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Overdue Invoices Dialog -->
            <OverdueInvoicesDialog
                v-model:open="showOverdueDialog"
                :invoices="overdueInvoices"
            />
        </template>
    </DashboardLayout>
</template>
