<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import PartnerFormDialog from '@/pages/partners/PartnerFormDialog.vue';
import type { OrderType, Partner } from '@/types/models';
import type { InertiaForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    form: InertiaForm<{
        partner_id: string;
        invoice_number: string;
        reference_number: string;
        invoice_date: string;
        due_date: string;
        order_type: OrderType;
        rental_start_date: string;
        rental_end_date: string;
        delivery_time: string;
        return_time: string;
        notes: string;
        terms: string;
    }>;
    partners: Partner[];
    timeOptions: string[];
}

defineProps<Props>();

const showPartnerDialog = ref(false);

function handlePartnerCreated() {
    router.reload({ only: ['partners'] });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Customer Selection -->
        <div class="space-y-2">
            <Label for="partner_id">Customer *</Label>
            <div class="flex gap-2">
                <Select v-model="form.partner_id" class="flex-1">
                    <SelectTrigger
                        id="partner_id"
                        :class="{
                            'border-destructive': form.errors.partner_id,
                        }"
                    >
                        <SelectValue placeholder="Select a customer" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="partner in partners"
                            :key="partner.id"
                            :value="String(partner.id)"
                        >
                            {{ partner.name }} ({{ partner.code }})
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    @click="showPartnerDialog = true"
                >
                    <Plus class="h-4 w-4" />
                </Button>
            </div>
            <p v-if="form.errors.partner_id" class="text-sm text-destructive">
                {{ form.errors.partner_id }}
            </p>
        </div>

        <!-- Invoice Code -->
        <div class="space-y-2">
            <Label for="invoice_number">Invoice Code</Label>
            <Input
                id="invoice_number"
                v-model="form.invoice_number"
                placeholder="Leave empty for auto-generation (e.g., INV-2026-0001)"
                :class="{
                    'border-destructive': form.errors.invoice_number,
                }"
            />
            <p
                v-if="form.errors.invoice_number"
                class="text-sm text-destructive"
            >
                {{ form.errors.invoice_number }}
            </p>
            <p class="text-xs text-muted-foreground">
                Optional: Leave blank to auto-generate, or enter a custom code
            </p>
        </div>

        <!-- Invoice Details Grid -->
        <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-2">
                <Label for="invoice_date">Invoice Date *</Label>
                <Input
                    id="invoice_date"
                    v-model="form.invoice_date"
                    type="date"
                    :class="{
                        'border-destructive': form.errors.invoice_date,
                    }"
                />
                <p
                    v-if="form.errors.invoice_date"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.invoice_date }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="due_date">Due Date *</Label>
                <Input
                    id="due_date"
                    v-model="form.due_date"
                    type="date"
                    :class="{
                        'border-destructive': form.errors.due_date,
                    }"
                />
                <p v-if="form.errors.due_date" class="text-sm text-destructive">
                    {{ form.errors.due_date }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="reference_number">Reference Number</Label>
                <Input
                    id="reference_number"
                    v-model="form.reference_number"
                    placeholder="Optional"
                />
            </div>
        </div>

        <!-- Order Type -->
        <div class="space-y-2">
            <Label>Order Type *</Label>
            <RadioGroup v-model="form.order_type" class="flex gap-4">
                <div class="flex items-center space-x-2">
                    <RadioGroupItem value="sales" id="sales" />
                    <Label for="sales" class="cursor-pointer font-normal"
                        >Sales</Label
                    >
                </div>
                <div class="flex items-center space-x-2">
                    <RadioGroupItem value="rental" id="rental" />
                    <Label for="rental" class="cursor-pointer font-normal"
                        >Rental</Label
                    >
                </div>
            </RadioGroup>
        </div>

        <!-- Rental Fields (Conditional) -->
        <div
            v-if="form.order_type === 'rental'"
            class="space-y-4 rounded-lg border bg-muted/50 p-4"
        >
            <h4 class="font-medium">Rental Information</h4>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="rental_start_date">Rental Start Date *</Label>
                    <Input
                        id="rental_start_date"
                        v-model="form.rental_start_date"
                        type="date"
                        :class="{
                            'border-destructive': form.errors.rental_start_date,
                        }"
                    />
                    <p
                        v-if="form.errors.rental_start_date"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.rental_start_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="rental_end_date">Rental End Date *</Label>
                    <Input
                        id="rental_end_date"
                        v-model="form.rental_end_date"
                        type="date"
                        :class="{
                            'border-destructive': form.errors.rental_end_date,
                        }"
                    />
                    <p
                        v-if="form.errors.rental_end_date"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.rental_end_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="delivery_time">Delivery Time *</Label>
                    <Select v-model="form.delivery_time">
                        <SelectTrigger
                            id="delivery_time"
                            :class="{
                                'border-destructive': form.errors.delivery_time,
                            }"
                        >
                            <SelectValue placeholder="Select time" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="time in timeOptions"
                                :key="time"
                                :value="time"
                            >
                                {{ time }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.delivery_time"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.delivery_time }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="return_time">Return Time *</Label>
                    <Select v-model="form.return_time">
                        <SelectTrigger
                            id="return_time"
                            :class="{
                                'border-destructive': form.errors.return_time,
                            }"
                        >
                            <SelectValue placeholder="Select time" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="time in timeOptions"
                                :key="time"
                                :value="time"
                            >
                                {{ time }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.return_time"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.return_time }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Notes and Terms -->
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="notes">Notes</Label>
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    placeholder="Additional notes..."
                    rows="3"
                />
            </div>

            <div class="space-y-2">
                <Label for="terms">Terms & Conditions</Label>
                <Textarea
                    id="terms"
                    v-model="form.terms"
                    placeholder="Terms and conditions..."
                    rows="3"
                />
            </div>
        </div>
    </div>

    <!-- Nested Partner Dialog -->
    <PartnerFormDialog
        v-model:open="showPartnerDialog"
        @success="handlePartnerCreated"
    />
</template>
