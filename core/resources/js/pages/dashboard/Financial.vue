<script setup lang="ts">
import EmptyState from '@/components/dashboard/EmptyState.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
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
import { formatCurrency, formatPercentage } from '@/composables/useFormatters';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { VisAxis, VisStackedBar, VisXYContainer } from '@unovis/vue';
import {
    DollarSign,
    PackageSearch,
    Percent,
    TrendingUp,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    activeTab: string;
    sharedData: {
        currentMonthRevenue: {
            total: number;
        };
    };
    monthlyComparison: Array<{
        week: string;
        current: number;
        previous: number;
    }>;
    topCustomers: Array<{
        partner_id: number;
        partner_name: string;
        total_revenue: number;
    }>;
    topProducts: Array<{
        product_id: number;
        product_name: string;
        total_revenue: number;
    }>;
    metrics: {
        average_invoice_value: { current: number; previous: number };
        payment_collection_rate: { current: number; previous: number };
        total_discounts: { current: number; previous: number };
    };
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

function viewCustomer(partnerId: number) {
    router.visit(`/customers/${partnerId}`);
}

function viewProduct(productId: number) {
    router.visit(`/products/${productId}`);
}

const comparisonChartConfig: ChartConfig = {
    current: {
        label: 'Current Month',
        color: 'hsl(var(--chart-1))',
    },
    previous: {
        label: 'Previous Month',
        color: 'hsl(var(--chart-2))',
    },
};

const comparisonData = computed(() => {
    return props.monthlyComparison.map((item: any) => ({
        week: item.week,
        current: item.current,
        previous: item.previous,
    }));
});
</script>

<template>
    <Head title="Financial Dashboard" />

    <DashboardLayout
        :active-tab="activeTab as 'overview' | 'financial' | 'operations'"
    >
        <template v-if="!hasData">
            <EmptyState
                :icon="PackageSearch"
                title="No financial data yet"
                description="Create invoices to start tracking your financial performance and insights."
                :primary-action="{
                    label: 'Create Your First Invoice',
                    onClick: createInvoice,
                }"
            />
        </template>

        <template v-else>
            <!-- Top Financial Metrics -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    title="Average Invoice Value"
                    :value="
                        formatCurrency(metrics.average_invoice_value.current)
                    "
                    :icon="DollarSign"
                    :on-click="() => navigateToInvoices()"
                />

                <StatCard
                    title="Collection Rate"
                    :value="
                        formatPercentage(
                            metrics.payment_collection_rate.current,
                        )
                    "
                    :icon="TrendingUp"
                    :on-click="
                        () =>
                            navigateToInvoices({ status: ['Paid', 'Partial'] })
                    "
                />

                <StatCard
                    title="Total Discounts"
                    :value="formatCurrency(metrics.total_discounts.current)"
                    :icon="Percent"
                    :on-click="() => navigateToInvoices({ has_discount: true })"
                >
                    <template>
                        <div class="mt-2 text-xs text-muted-foreground">
                            {{
                                formatPercentage(
                                    (metrics.total_discounts.current /
                                        sharedData.currentMonthRevenue.total) *
                                        100,
                                )
                            }}
                            of revenue
                        </div>
                    </template>
                </StatCard>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Monthly Comparison -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Monthly Comparison</CardTitle>
                        <CardDescription
                            >Current month vs previous month by
                            week</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            :config="comparisonChartConfig"
                            class="h-[300px]"
                        >
                            <VisXYContainer
                                :data="comparisonData"
                                :margin="{
                                    top: 10,
                                    right: 10,
                                    bottom: 40,
                                    left: 60,
                                }"
                            >
                                <VisStackedBar
                                    :x="(d: any) => d.week"
                                    :y="[
                                        (d: any) => d.current,
                                        (d: any) => d.previous,
                                    ]"
                                    :color="[
                                        'var(--color-current)',
                                        'var(--color-previous)',
                                    ]"
                                    :rounded-corners="2"
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

                <!-- Top Customers -->
                <Card>
                    <CardHeader>
                        <CardTitle>Top 5 Customers</CardTitle>
                        <CardDescription
                            >By total revenue this month</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="customer in topCustomers"
                                :key="customer.partner_id"
                                class="flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                @click="viewCustomer(customer.partner_id)"
                            >
                                <div class="space-y-1">
                                    <p class="text-sm font-medium">
                                        {{ customer.partner_name }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">
                                        {{
                                            formatCurrency(
                                                customer.total_revenue,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Products -->
                <Card>
                    <CardHeader>
                        <CardTitle>Top 5 Products</CardTitle>
                        <CardDescription
                            >By total revenue this month</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="product in topProducts"
                                :key="product.product_id"
                                class="flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                @click="viewProduct(product.product_id)"
                            >
                                <div class="space-y-1">
                                    <p class="text-sm font-medium">
                                        {{ product.product_name }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">
                                        {{
                                            formatCurrency(
                                                product.total_revenue,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </DashboardLayout>
</template>
