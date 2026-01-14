<script setup lang="ts">
import EmptyState from '@/components/dashboard/EmptyState.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
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
    ChartContainer,
    ChartTooltip,
    type ChartConfig,
} from '@/components/ui/chart';
import { formatCurrency } from '@/composables/useFormatters';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue';
import {
    Activity,
    AlertCircle,
    DollarSign,
    PackageSearch,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    activeTab: string;
    sharedData: {
        currentMonthRevenue: {
            total: number;
            paid: number;
            outstanding: number;
        };
        revenueChange: number;
        outstandingBalance: number;
        overdueBalance: number;
        outstandingCount: number;
        activeRentalsCount: number;
        pendingInvoicesCount: number;
        newCustomersCount: number;
        customersChange: number;
        dailyRevenue: number[];
    };
    revenueChart: Array<{
        month: string;
        sales: number;
        rentals: number;
        total: number;
    }>;
    recentInvoices: Array<{
        id: number;
        invoice_number: string;
        partner_name: string;
        invoice_date: string;
        total_amount: number;
        status: string;
    }>;
    statusDistribution: Record<string, number>;
    hasData: boolean;
}

const props = defineProps<Props>();

function navigateToInvoices(params: Record<string, any> = {}) {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            query.set(key, value.join(','));
        } else {
            query.set(key, value);
        }
    });
    router.visit(`/invoices${query.toString() ? '?' + query.toString() : ''}`);
}

function createInvoice() {
    router.visit('/invoices', { data: { create: true } });
}

function viewInvoice(id: number) {
    router.visit(`/invoices/${id}`);
}

function getStatusBadgeVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'Paid':
            return 'default';
        case 'Unpaid':
            return 'destructive';
        case 'Partial':
            return 'secondary';
        default:
            return 'outline';
    }
}

const statusColors = {
    Paid: 'hsl(var(--chart-3))',
    Unpaid: 'hsl(var(--chart-4))',
    Partial: 'hsl(var(--chart-5))',
    Void: 'hsl(var(--muted))',
};

const revenueChartConfig: ChartConfig = {
    sales: {
        label: 'Sales',
        color: 'hsl(var(--chart-1))',
    },
    rentals: {
        label: 'Rentals',
        color: 'hsl(var(--chart-2))',
    },
};

const chartData = computed(() => {
    return props.revenueChart.map((item: any) => ({
        month: item.month,
        sales: item.sales,
        rentals: item.rentals,
    }));
});
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout
        :active-tab="activeTab as 'overview' | 'financial' | 'operations'"
    >
        <template v-if="!hasData">
            <EmptyState
                :icon="PackageSearch"
                title="No data to display yet"
                description="Get started by creating your first invoice to see your business metrics and insights."
                :primary-action="{
                    label: 'Create Your First Invoice',
                    onClick: createInvoice,
                }"
            />
        </template>

        <template v-else>
            <!-- Top Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    title="Revenue This Month"
                    :value="
                        formatCurrency(sharedData.currentMonthRevenue.total)
                    "
                    :change="{
                        value: sharedData.revenueChange,
                        isPositive: sharedData.revenueChange >= 0,
                    }"
                    :icon="DollarSign"
                    :sparkline-data="sharedData.dailyRevenue"
                    :on-click="
                        () =>
                            navigateToInvoices({
                                date_from: new Date(
                                    new Date().getFullYear(),
                                    new Date().getMonth(),
                                    1,
                                )
                                    .toISOString()
                                    .split('T')[0],
                                date_to: new Date().toISOString().split('T')[0],
                            })
                    "
                />

                <StatCard
                    title="Outstanding Payments"
                    :value="formatCurrency(sharedData.outstandingBalance)"
                    :icon="AlertCircle"
                    :on-click="
                        () =>
                            navigateToInvoices({
                                status: ['Unpaid', 'Partial'],
                            })
                    "
                    :is-empty="sharedData.outstandingBalance === 0"
                    :empty-state="{
                        title: 'All Caught Up!',
                        description: 'All payments have been collected',
                        ctaLabel: 'View Invoices',
                        ctaAction: () => navigateToInvoices(),
                    }"
                >
                    <template v-if="sharedData.overdueBalance > 0">
                        <div class="mt-2 text-xs text-destructive">
                            {{ formatCurrency(sharedData.overdueBalance) }}
                            overdue
                        </div>
                    </template>
                </StatCard>

                <StatCard
                    title="Active Operations"
                    :value="`${sharedData.activeRentalsCount} Rentals`"
                    :icon="Activity"
                    :on-click="
                        () => navigateToInvoices({ order_type: 'Rental' })
                    "
                >
                    <template>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{ sharedData.pendingInvoicesCount }} pending
                            invoices
                        </div>
                    </template>
                </StatCard>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Revenue Trend Chart -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Revenue Trend</CardTitle>
                        <CardDescription
                            >Sales vs Rental revenue over the last 6
                            months</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            :config="revenueChartConfig"
                            class="h-[300px]"
                        >
                            <VisXYContainer
                                :data="chartData"
                                :margin="{
                                    top: 10,
                                    right: 10,
                                    bottom: 40,
                                    left: 40,
                                }"
                            >
                                <VisArea
                                    :x="(d: any) => d.month"
                                    :y="[
                                        (d: any) => d.sales,
                                        (d: any) => d.rentals,
                                    ]"
                                    :color="[
                                        'var(--color-sales)',
                                        'var(--color-rentals)',
                                    ]"
                                    :opacity="0.2"
                                />
                                <VisLine
                                    :x="(d: any) => d.month"
                                    :y="[
                                        (d: any) => d.sales,
                                        (d: any) => d.rentals,
                                    ]"
                                    :color="[
                                        'var(--color-sales)',
                                        'var(--color-rentals)',
                                    ]"
                                />
                                <VisAxis
                                    type="x"
                                    :tick-format="(v: any) => v"
                                />
                                <VisAxis
                                    type="y"
                                    :tick-format="
                                        (v: number) => formatCurrency(v)
                                    "
                                />
                                <ChartTooltip cursor />
                            </VisXYContainer>
                        </ChartContainer>
                    </CardContent>
                </Card>

                <!-- Recent Invoices -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Invoices</CardTitle>
                        <CardDescription>Latest 10 invoices</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div
                                v-for="invoice in recentInvoices"
                                :key="invoice.id"
                                class="flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                @click="viewInvoice(invoice.id)"
                            >
                                <div class="space-y-1">
                                    <p class="text-sm font-medium">
                                        {{ invoice.invoice_number }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ invoice.partner_name }}
                                    </p>
                                </div>
                                <div class="space-y-1 text-right">
                                    <p class="text-sm font-medium">
                                        {{
                                            formatCurrency(invoice.total_amount)
                                        }}
                                    </p>
                                    <Badge
                                        :variant="
                                            getStatusBadgeVariant(
                                                invoice.status,
                                            )
                                        "
                                        class="text-xs"
                                    >
                                        {{ invoice.status }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Button
                                variant="outline"
                                class="w-full"
                                @click="navigateToInvoices()"
                            >
                                View All Invoices
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Distribution -->
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Status</CardTitle>
                        <CardDescription
                            >Distribution by payment status</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <!-- Placeholder for pie chart -->
                        <div class="space-y-3">
                            <div
                                v-for="(count, status) in statusDistribution"
                                :key="status"
                                class="flex cursor-pointer items-center justify-between rounded-lg p-2 transition-colors hover:bg-muted/50"
                                @click="
                                    navigateToInvoices({ status: [status] })
                                "
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                statusColors[
                                                    status as keyof typeof statusColors
                                                ],
                                        }"
                                    />
                                    <span class="text-sm font-medium">{{
                                        status
                                    }}</span>
                                </div>
                                <span class="text-sm text-muted-foreground">{{
                                    count
                                }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </DashboardLayout>
</template>
