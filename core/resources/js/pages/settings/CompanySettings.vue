<script setup lang="ts">
import type { CompanySetting } from '@/types/models.d';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Info, Upload, X } from 'lucide-vue-next';

interface Props {
    settings: CompanySetting;
}

const props = defineProps<Props>();

const breadcrumbItems = [
    {
        title: 'Company Settings',
        href: '/settings/company',
    },
];

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.settings.logo_url);
const selectedFile = ref<File | null>(null);

const form = useForm({
    company_name: props.settings.company_name,
    email: props.settings.email || '',
    phone: props.settings.phone || '',
    address: props.settings.address || '',
    city: props.settings.city || '',
    postal_code: props.settings.postal_code || '',
    website: props.settings.website || '',
    tax_number: props.settings.tax_number || '',
    invoice_number_prefix: props.settings.invoice_number_prefix,
    invoice_default_terms: props.settings.invoice_default_terms || '',
    invoice_default_notes: props.settings.invoice_default_notes || '',
    logo: null as File | null,
    remove_logo: 0,
});

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        selectedFile.value = file;
        form.logo = file;
        form.remove_logo = 0;

        // Generate preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function removeLogo() {
    form.remove_logo = 1;
    form.logo = null;
    selectedFile.value = null;
    previewUrl.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    const formData = new FormData();

    // Append all text fields
    Object.keys(form.data()).forEach((key) => {
        const value = form.data()[key as keyof typeof form.data];
        if (value !== null && value !== undefined && key !== 'logo') {
            formData.append(key, String(value));
        }
    });

    // Append logo if selected
    if (form.logo) {
        formData.append('logo', form.logo);
    }

    router.post('/settings/company', formData, {
        preserveScroll: true,
        onSuccess: () => {
            selectedFile.value = null;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Company Settings" />

        <h1 class="sr-only">Company Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <!-- Company Information -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Company Information"
                        description="Basic information about your company that appears on invoices"
                    />

                    <div class="space-y-4">
                        <!-- Company Logo -->
                        <div class="grid gap-2">
                            <Label for="logo">Company Logo</Label>
                            <div class="flex items-start gap-4">
                                <div
                                    v-if="previewUrl"
                                    class="relative h-24 w-24 overflow-hidden rounded-lg border bg-muted"
                                >
                                    <img
                                        :src="previewUrl"
                                        alt="Company Logo"
                                        class="h-full w-full object-contain"
                                    />
                                    <button
                                        type="button"
                                        @click="removeLogo"
                                        class="absolute top-1 right-1 rounded-full bg-destructive p-1 text-destructive-foreground hover:bg-destructive/90"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </div>
                                <div
                                    v-else
                                    class="flex h-24 w-24 items-center justify-center rounded-lg border bg-muted"
                                >
                                    <Upload
                                        class="h-8 w-8 text-muted-foreground"
                                    />
                                </div>
                                <div class="flex-1 space-y-2">
                                    <input
                                        ref="fileInput"
                                        id="logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg"
                                        class="sr-only"
                                        @change="handleFileChange"
                                    />
                                    <label
                                        for="logo"
                                        class="inline-flex h-9 cursor-pointer items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                                    >
                                        Choose Image
                                    </label>
                                    <p class="text-xs text-muted-foreground">
                                        PNG, JPG or JPEG. Max 2MB.
                                    </p>
                                    <Alert class="mt-2">
                                        <Info class="h-4 w-4" />
                                        <AlertDescription>
                                            Recommended: 800x400px (2:1 aspect
                                            ratio)
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            </div>
                            <InputError :message="form.errors.logo" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="company_name">Company Name *</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                required
                                placeholder="Your Company Name"
                            />
                            <InputError :message="form.errors.company_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="contact@company.com"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="+62 123 456 7890"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Textarea
                                id="address"
                                v-model="form.address"
                                placeholder="Street address"
                                rows="2"
                            />
                            <InputError :message="form.errors.address" />
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
                                <Label for="postal_code">Postal Code</Label>
                                <Input
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    placeholder="12345"
                                />
                                <InputError
                                    :message="form.errors.postal_code"
                                />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="website">Website</Label>
                            <Input
                                id="website"
                                v-model="form.website"
                                type="url"
                                placeholder="https://yourcompany.com"
                            />
                            <InputError :message="form.errors.website" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="tax_number">Tax Number</Label>
                            <Input
                                id="tax_number"
                                v-model="form.tax_number"
                                placeholder="Tax identification number"
                            />
                            <p class="text-xs text-muted-foreground">
                                Will be displayed on invoices if provided
                            </p>
                            <InputError :message="form.errors.tax_number" />
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Invoice Configuration -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Invoice Configuration"
                        description="Settings related to invoice generation"
                    />

                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="invoice_number_prefix"
                                >Invoice Number Prefix *</Label
                            >
                            <Input
                                id="invoice_number_prefix"
                                v-model="form.invoice_number_prefix"
                                required
                                placeholder="INV"
                                maxlength="10"
                            />
                            <p class="text-xs text-muted-foreground">
                                Format will be:
                                {{ form.invoice_number_prefix }}-2026-0001. Only
                                letters, numbers, dashes, and underscores
                                allowed.
                            </p>
                            <InputError
                                :message="form.errors.invoice_number_prefix"
                            />
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Invoice Defaults -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Invoice Defaults"
                        description="Default terms and notes that will auto-populate on new invoices"
                    />

                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="invoice_default_terms"
                                >Default Invoice Terms</Label
                            >
                            <Textarea
                                id="invoice_default_terms"
                                v-model="form.invoice_default_terms"
                                placeholder="Payment terms, return policy, etc."
                                rows="4"
                            />
                            <p class="text-xs text-muted-foreground">
                                These terms will appear on new invoices by
                                default
                            </p>
                            <InputError
                                :message="form.errors.invoice_default_terms"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="invoice_default_notes"
                                >Default Invoice Notes</Label
                            >
                            <Textarea
                                id="invoice_default_notes"
                                v-model="form.invoice_default_notes"
                                placeholder="Thank you for your business, special instructions, etc."
                                rows="4"
                            />
                            <p class="text-xs text-muted-foreground">
                                These notes will appear on new invoices by
                                default
                            </p>
                            <InputError
                                :message="form.errors.invoice_default_notes"
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center gap-4">
                    <Button @click="submit" :disabled="form.processing">
                        Save Changes
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="form.recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
