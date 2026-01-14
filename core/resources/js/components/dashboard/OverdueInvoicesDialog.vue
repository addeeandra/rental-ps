<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCurrency, formatDate } from '@/composables/useFormatters';
import { router } from '@inertiajs/vue3';
import { ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OverdueInvoice {
    id: number;
    invoice_number: string;
    partner_name: string;
    total_amount: number;
    balance: number;
    due_date: string;
    days_overdue: number;
    status: string;
}

interface Props {
    open: boolean;
    invoices: OverdueInvoice[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const sortBy = ref<'amount' | 'days'>('days');
const sortOrder = ref<'asc' | 'desc'>('desc');

const sortedInvoices = computed(() => {
    const sorted = [...props.invoices];
    sorted.sort((a, b) => {
        const multiplier = sortOrder.value === 'asc' ? 1 : -1;
        if (sortBy.value === 'amount') {
            return (a.balance - b.balance) * multiplier;
        }
        return (a.days_overdue - b.days_overdue) * multiplier;
    });
    return sorted;
});

function toggleSort(field: 'amount' | 'days') {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'desc';
    }
}

function viewInvoice(id: number) {
    router.visit(`/invoices/${id}`);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-h-[90vh] max-w-full overflow-hidden md:max-w-[90vw]"
        >
            <DialogHeader>
                <DialogTitle>Overdue Invoices</DialogTitle>
                <DialogDescription>
                    All invoices that are past their due date
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[70vh] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 border-b bg-muted/50">
                        <tr>
                            <th class="p-2 text-left font-medium">Invoice</th>
                            <th class="p-2 text-left font-medium">Customer</th>
                            <th class="p-2 text-left font-medium">Due Date</th>
                            <th
                                class="cursor-pointer p-2 text-right font-medium hover:bg-muted"
                                @click="toggleSort('amount')"
                            >
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    Amount
                                    <ArrowUpDown class="h-3 w-3" />
                                </div>
                            </th>
                            <th
                                class="cursor-pointer p-2 text-center font-medium hover:bg-muted"
                                @click="toggleSort('days')"
                            >
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    Days Overdue
                                    <ArrowUpDown class="h-3 w-3" />
                                </div>
                            </th>
                            <th class="p-2 text-center font-medium">Status</th>
                            <th class="p-2 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="invoice in sortedInvoices"
                            :key="invoice.id"
                            class="cursor-pointer hover:bg-muted/50"
                            @click="viewInvoice(invoice.id)"
                        >
                            <td class="p-2 font-medium">
                                {{ invoice.invoice_number }}
                            </td>
                            <td class="p-2">{{ invoice.partner_name }}</td>
                            <td class="p-2 text-muted-foreground">
                                {{ formatDate(invoice.due_date) }}
                            </td>
                            <td class="p-2 text-right">
                                {{ formatCurrency(invoice.balance) }}
                            </td>
                            <td class="p-2 text-center">
                                <Badge variant="destructive">
                                    {{ invoice.days_overdue }} days
                                </Badge>
                            </td>
                            <td class="p-2 text-center">
                                <Badge
                                    :variant="
                                        invoice.status === 'Unpaid'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{ invoice.status }}
                                </Badge>
                            </td>
                            <td class="p-2 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click.stop="viewInvoice(invoice.id)"
                                >
                                    View
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DialogContent>
    </Dialog>
</template>
