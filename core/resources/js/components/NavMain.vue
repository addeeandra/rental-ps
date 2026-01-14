<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();

// Group items by their group headers
const groupedItems = computed(() => {
    const groups: Array<{ label: string; items: Array<NavItem> }> = [];
    let currentGroup: { label: string; items: Array<NavItem> } | null = null;

    for (const item of props.items) {
        if ('group' in item) {
            // Start a new group
            currentGroup = { label: item.group, items: [] };
            groups.push(currentGroup);
        } else if (currentGroup) {
            // Add item to current group
            currentGroup.items.push(item);
        }
    }

    return groups;
});
</script>

<template>
    <template v-for="group in groupedItems" :key="group.label">
        <SidebarGroup class="px-2 py-0">
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem
                    v-for="item in group.items"
                    :key="'title' in item ? item.title : ''"
                >
                    <template v-if="'title' in item">
                        <SidebarMenuButton
                            as-child
                            :is-active="urlIsActive(item.href)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href" prefetch>
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </template>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </template>
</template>
