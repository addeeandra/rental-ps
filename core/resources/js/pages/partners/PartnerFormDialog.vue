<script setup lang="ts">
import HeadingFormSection from '@/components/HeadingFormSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Partner } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    open: boolean | Partner;
    partner?: Partner | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
    cancel: [];
}>();

const isOpen = computed({
    get: () => !!props.open,
    set: (value) => {
        if (!value) {
            emit('update:open', false);
            emit('cancel');
        }
    },
});

const isEditing = computed(() => !!props.partner);

const form = useForm({
    code: props.partner?.code || '',
    type: props.partner?.type || 'Client',
    name: props.partner?.name || '',
    email: props.partner?.email || '',
    phone: props.partner?.phone || '',
    mobile_phone: props.partner?.mobile_phone || '',
    address_line_1: props.partner?.address_line_1 || '',
    address_line_2: props.partner?.address_line_2 || '',
    city: props.partner?.city || '',
    province: props.partner?.province || '',
    postal_code: props.partner?.postal_code || '',
    country: props.partner?.country || 'Indonesia',
    gmap_url: props.partner?.gmap_url || '',
    website: props.partner?.website || '',
    notes: props.partner?.notes || '',
});

function submit() {
    if (isEditing.value && props.partner) {
        form.patch(`/partners/${props.partner.id}`, {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    } else {
        form.post('/partners', {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    }
}

watch(
    () => props.open,
    (value) => {
        if (value && props.partner) {
            form.code = props.partner.code;
            form.type = props.partner.type;
            form.name = props.partner.name;
            form.email = props.partner.email || '';
            form.phone = props.partner.phone || '';
            form.mobile_phone = props.partner.mobile_phone || '';
            form.address_line_1 = props.partner.address_line_1 || '';
            form.address_line_2 = props.partner.address_line_2 || '';
            form.city = props.partner.city || '';
            form.province = props.partner.province || '';
            form.postal_code = props.partner.postal_code || '';
            form.country = props.partner.country || 'Indonesia';
            form.gmap_url = props.partner.gmap_url || '';
            form.website = props.partner.website || '';
            form.notes = props.partner.notes || '';
        } else if (!value) {
            form.reset();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Partner' : 'New Partner'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update partner information.'
                            : 'Add a new partner to your contacts.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <HeadingFormSection title="Basic Information" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="code">
                                Code
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    (Optional)
                                </span>
                            </Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                :placeholder="
                                    isEditing ? '' : 'Auto-generated (P-XXXX)'
                                "
                            />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="type"
                                >Type
                                <span class="text-destructive">*</span></Label
                            >
                            <select
                                id="type"
                                v-model="form.type"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="Client">Client</option>
                                <option value="Supplier">Supplier</option>
                                <option value="Supplier & Client">
                                    Supplier & Client
                                </option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Partner name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="031-1234567"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="mobile_phone">Mobile Phone</Label>
                        <Input
                            id="mobile_phone"
                            v-model="form.mobile_phone"
                            placeholder="628123456789"
                        />
                        <InputError :message="form.errors.mobile_phone" />
                    </div>
                </div>

                <!-- Address Information -->
                <div class="space-y-4">
                    <HeadingFormSection title="Address" />

                    <div class="grid gap-2">
                        <Label for="address_line_1">Address Line 1</Label>
                        <Input
                            id="address_line_1"
                            v-model="form.address_line_1"
                            placeholder="Street address"
                        />
                        <InputError :message="form.errors.address_line_1" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address_line_2">Address Line 2</Label>
                        <Input
                            id="address_line_2"
                            v-model="form.address_line_2"
                            placeholder="Apartment, suite, unit, building, floor, etc."
                        />
                        <InputError :message="form.errors.address_line_2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="city">City</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                placeholder="City"
                            />
                            <InputError :message="form.errors.city" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="province">Province</Label>
                            <Input
                                id="province"
                                v-model="form.province"
                                placeholder="Province"
                            />
                            <InputError :message="form.errors.province" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="postal_code">Postal Code</Label>
                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                placeholder="60123"
                            />
                            <InputError :message="form.errors.postal_code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input
                                id="country"
                                v-model="form.country"
                                placeholder="Indonesia"
                            />
                            <InputError :message="form.errors.country" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="gmap_url">Google Maps URL</Label>
                        <Input
                            id="gmap_url"
                            v-model="form.gmap_url"
                            type="url"
                            placeholder="https://www.google.com/maps/place/..."
                        />
                        <InputError :message="form.errors.gmap_url" />
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="space-y-4">
                    <HeadingFormSection title="Additional Information" />

                    <div class="grid gap-2">
                        <Label for="website">Website</Label>
                        <Input
                            id="website"
                            v-model="form.website"
                            type="url"
                            placeholder="https://example.com"
                        />
                        <InputError :message="form.errors.website" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Additional notes about this partner..."
                            rows="3"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Update' : 'Create' }} Partner
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
