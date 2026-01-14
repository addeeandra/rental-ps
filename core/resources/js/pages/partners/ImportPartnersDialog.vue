<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    open: boolean;
    importUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    importUrl: '/partners/import',
});
const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const selectedFile = ref<File | null>(null);
const isProcessing = ref(false);
const importResult = ref<{
    success: boolean;
    summary: {
        total: number;
        imported: number;
        skipped: number;
    };
    errors: Array<{
        row: number;
        name: string;
        errors: string[];
    }>;
} | null>(null);
const showErrors = ref(false);

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
        importResult.value = null;
    }
}

async function handleImport() {
    if (!selectedFile.value) return;

    isProcessing.value = true;
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await fetch(props.importUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
                Accept: 'application/json',
            },
        });

        const data = await response.json();
        importResult.value = data;

        if (data.success && data.summary.imported > 0) {
            // Reload the partners list
            router.reload({ only: ['partners'] });
        }
    } catch (error) {
        console.error('Import error:', error);
        importResult.value = {
            success: false,
            summary: { total: 0, imported: 0, skipped: 0 },
            errors: [
                {
                    row: 0,
                    name: 'Error',
                    errors: ['Failed to import file. Please try again.'],
                },
            ],
        };
    } finally {
        isProcessing.value = false;
    }
}

function reset() {
    selectedFile.value = null;
    importResult.value = null;
    showErrors.value = false;
    const fileInput = document.getElementById('csv-file') as HTMLInputElement;
    if (fileInput) {
        fileInput.value = '';
    }
}

function close() {
    if (!isProcessing.value) {
        reset();
        isOpen.value = false;
        if (
            importResult.value?.success &&
            importResult.value.summary.imported > 0
        ) {
            emit('success');
        }
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen" @update:open="(value) => !value && close()">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>Import Partners</DialogTitle>
                <DialogDescription>
                    Upload a CSV file to import multiple partners at once.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Info Alert -->
                <Alert>
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription class="text-sm">
                        <strong>CSV Format Requirements:</strong>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            <li>Semicolon (;) delimiter</li>
                            <li>UTF-8 encoding for special characters</li>
                            <li>Required fields: type, name</li>
                            <li>Duplicate emails will be skipped</li>
                        </ul>
                        <a
                            href="/partners/template"
                            class="mt-2 inline-block text-xs text-primary underline-offset-4 hover:underline"
                        >
                            Download template file
                        </a>
                    </AlertDescription>
                </Alert>

                <!-- File Input -->
                <div class="grid gap-2">
                    <label
                        for="csv-file"
                        class="flex h-32 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-input bg-background transition-colors hover:border-primary hover:bg-accent"
                        :class="{
                            'pointer-events-none opacity-50': isProcessing,
                        }"
                    >
                        <Upload class="mb-2 h-8 w-8 text-muted-foreground" />
                        <p class="text-sm font-medium">
                            {{
                                selectedFile
                                    ? selectedFile.name
                                    : 'Click to select CSV file'
                            }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            CSV files only, max 10MB
                        </p>
                    </label>
                    <input
                        id="csv-file"
                        type="file"
                        accept=".csv,text/csv"
                        class="sr-only"
                        @change="handleFileChange"
                        :disabled="isProcessing"
                    />
                </div>

                <!-- Processing State -->
                <div
                    v-if="isProcessing"
                    class="flex items-center justify-center gap-3 rounded-lg border border-input bg-muted p-4"
                >
                    <Spinner class="h-5 w-5" />
                    <p class="text-sm font-medium">Importing partners...</p>
                </div>

                <!-- Success/Error Results -->
                <Alert
                    v-if="importResult && !isProcessing"
                    :variant="
                        importResult.summary.skipped > 0 ? 'default' : 'default'
                    "
                >
                    <CheckCircle2
                        v-if="importResult.summary.imported > 0"
                        class="h-4 w-4 text-green-600"
                    />
                    <AlertCircle v-else class="h-4 w-4" />
                    <AlertDescription>
                        <div class="space-y-2">
                            <p class="font-semibold">
                                Successfully imported
                                {{ importResult.summary.imported }} of
                                {{ importResult.summary.total }} partners.
                                <span
                                    v-if="importResult.summary.skipped > 0"
                                    class="text-destructive"
                                >
                                    {{ importResult.summary.skipped }} rows
                                    skipped.
                                </span>
                            </p>

                            <!-- Error Details -->
                            <Collapsible
                                v-if="importResult.errors.length > 0"
                                v-model:open="showErrors"
                            >
                                <CollapsibleTrigger
                                    class="flex items-center gap-2 text-sm font-medium hover:underline"
                                >
                                    <ChevronDown
                                        class="h-4 w-4 transition-transform"
                                        :class="{ 'rotate-180': showErrors }"
                                    />
                                    View skipped rows ({{
                                        importResult.errors.length
                                    }})
                                </CollapsibleTrigger>
                                <CollapsibleContent class="mt-3 space-y-2">
                                    <div
                                        v-for="error in importResult.errors"
                                        :key="error.row"
                                        class="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm"
                                    >
                                        <p class="font-semibold">
                                            Row {{ error.row }}:
                                            {{ error.name }}
                                        </p>
                                        <ul
                                            class="mt-1 list-inside list-disc space-y-1 text-xs"
                                        >
                                            <li
                                                v-for="(
                                                    err, idx
                                                ) in error.errors"
                                                :key="idx"
                                            >
                                                {{ err }}
                                            </li>
                                        </ul>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </div>
                    </AlertDescription>
                </Alert>
            </div>

            <DialogFooter>
                <Button
                    v-if="importResult"
                    variant="outline"
                    @click="reset"
                    :disabled="isProcessing"
                >
                    Import Another
                </Button>
                <Button
                    v-if="!importResult"
                    variant="outline"
                    @click="close"
                    :disabled="isProcessing"
                >
                    Cancel
                </Button>
                <Button
                    v-if="!importResult"
                    @click="handleImport"
                    :disabled="!selectedFile || isProcessing"
                >
                    <Upload class="mr-2 h-4 w-4" />
                    Import
                </Button>
                <Button v-else @click="close"> Done </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
