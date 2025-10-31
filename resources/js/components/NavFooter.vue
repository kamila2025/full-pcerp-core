<script setup>
import { useAbility } from '@casl/vue';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';

defineProps({
  items: { type: Array, required: true },
});

const { can } = useAbility();

const canViewNavMenuGroup = item => {
  if (!item.items) return can(item.action)

  const hasAnyVisibleChild = item.items.some(i => can(i.action));

  if (!item.action) return hasAnyVisibleChild

  return can(item.action) && hasAnyVisibleChild
};
</script>

<template>
  <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
    <SidebarGroupContent>
      <SidebarMenu>
        <template
          v-for="item in items"
          :key="item.title"
        >
          <SidebarMenuItem v-if="canViewNavMenuGroup(item)">
            <SidebarMenuButton
              :is-active="$page.url.replace(/\?.*$/, '').includes(item.url) && !item.items?.some(subItem => $page.url.replace(/\?.*$/, '').includes(subItem.url))"
              as-child
              class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
            >
              <a
                :href="item.url"
                :target="item.target"
                rel="noopener noreferrer"
              >
                <component :is="item.icon" />
                <span>{{ item.title }}</span>
              </a>
            </SidebarMenuButton>
            <SidebarMenuSub v-if="item.items?.length > 0 && item.items.some(subItem => $page.url.replace(/\?.*$/, '').includes(subItem.url))">
              <SidebarMenuSubItem
                v-for="subItem in item.items"
                :key="subItem.title"
              >
                <SidebarMenuSubButton
                  :is-active="$page.url.replace(/\?.*$/, '').includes(subItem.url)"
                  as-child
                >
                  <a :href="subItem.url">
                    <span>{{ subItem.title }}</span>
                  </a>
                </SidebarMenuSubButton>
              </SidebarMenuSubItem>
            </SidebarMenuSub>
          </SidebarMenuItem>
        </template>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
