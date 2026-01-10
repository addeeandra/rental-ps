<script setup lang="ts">
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
import type { Category } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    open: boolean | Category;
    category?: Category | null;
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

const isEditing = computed(() => !!props.category);

const form = useForm({
    name: props.category?.name || '',
    description: props.category?.description || '',
});

function submit() {
    if (isEditing.value && props.category) {
        form.patch(`/categories/${props.category.id}`, {
            onSuccess: () => {
                emit('success');
                form.reset();
            },
        });
    } else {
        form.post('/categories', {
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
        if (value && props.category) {
            form.name = props.category.name;
            form.description = props.category.description || '';
        } else if (!value) {
            form.reset();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>{{
                    isEditing ? 'Edit Category' : 'New Category'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update category information.'
                            : 'Add a new category for product classification.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Category name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Brief description of this category"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <p v-if="!isEditing" class="text-sm text-muted-foreground">
                        Category code will be auto-generated (CAT-XXXX).
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
