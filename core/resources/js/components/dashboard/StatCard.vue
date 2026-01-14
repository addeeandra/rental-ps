<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPercentage } from '@/composables/useFormatters';
import { TrendingDown, TrendingUp } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Props {
    title: string;
    value: string;
    change?: {
        value: number;
        isPositive: boolean;
    };
    icon?: Component;
    sparklineData?: number[];
    onClick?: () => void;
    isEmpty?: boolean;
    emptyState?: {
        title: string;
        description: string;
        ctaLabel: string;
        ctaAction: () => void;
    };
}

const props = withDefaults(defineProps<Props>(), {
    isEmpty: false,
});
</script>

<template>
    <Card
        :class="[
            'transition-all',
            onClick && !isEmpty ? 'cursor-pointer hover:shadow-md' : '',
        ]"
        @click="onClick && !isEmpty ? onClick() : null"
    >
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
        >
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
            <component
                v-if="icon"
                :is="icon"
                class="h-4 w-4 text-muted-foreground"
            />
        </CardHeader>
        <CardContent>
            <template v-if="!isEmpty">
                <div class="text-2xl font-bold">{{ value }}</div>
                <div
                    v-if="change"
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <TrendingUp
                        v-if="change.isPositive"
                        class="h-3 w-3 text-green-500"
                    />
                    <TrendingDown v-else class="h-3 w-3 text-red-500" />
                    <span
                        :class="
                            change.isPositive
                                ? 'text-green-500'
                                : 'text-red-500'
                        "
                    >
                        {{ formatPercentage(change.value) }}
                    </span>
                    <span>from last month</span>
                </div>

                <!-- Sparkline placeholder - will be enhanced later with actual chart -->
                <div
                    v-if="sparklineData && sparklineData.length"
                    class="mt-2 h-8"
                >
                    <div class="flex h-full items-end gap-0.5">
                        <div
                            v-for="(value, index) in sparklineData.slice(-15)"
                            :key="index"
                            class="flex-1 rounded-sm bg-primary/20"
                            :style="{
                                height: `${(value / Math.max(...sparklineData)) * 100}%`,
                            }"
                        />
                    </div>
                </div>
            </template>

            <template v-else-if="emptyState">
                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground">
                        {{ emptyState.description }}
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        @click.stop="emptyState.ctaAction"
                    >
                        {{ emptyState.ctaLabel }}
                    </Button>
                </div>
            </template>
        </CardContent>
    </Card>
</template>
