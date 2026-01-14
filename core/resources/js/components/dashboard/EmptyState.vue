<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Component } from 'vue';

interface Props {
    icon?: Component;
    title: string;
    description: string;
    primaryAction?: {
        label: string;
        onClick: () => void;
    };
    secondaryAction?: {
        label: string;
        onClick: () => void;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <Card class="flex min-h-[400px] flex-col items-center justify-center">
        <CardContent class="flex flex-col items-center gap-4 py-8 text-center">
            <div class="rounded-full bg-muted p-4">
                <component
                    v-if="icon"
                    :is="icon"
                    class="h-8 w-8 text-muted-foreground"
                />
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-semibold">{{ title }}</h3>
                <p class="max-w-md text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button v-if="primaryAction" @click="primaryAction.onClick">
                    {{ primaryAction.label }}
                </Button>
                <Button
                    v-if="secondaryAction"
                    variant="outline"
                    @click="secondaryAction.onClick"
                >
                    {{ secondaryAction.label }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
