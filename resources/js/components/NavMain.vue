<script setup>
import { useAbility } from '@casl/vue';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { ChevronRight } from 'lucide-vue-next';

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
  <SidebarGroup>
    <SidebarMenu>
      <template
        v-for="item in items"
        :key="item.title"
      >
        <Collapsible
          v-if="item.items?.length > 0 && canViewNavMenuGroup(item)"
          as-child
          :default-open="item.items.some(subItem => $page.url.replace(/\?.*$/, '').includes(subItem.url))"
          class="group/collapsible"
        >
          <SidebarMenuItem>
            <CollapsibleTrigger as-child>
              <SidebarMenuButton :tooltip="item.title">
                <component
                  :is="item.icon"
                  v-if="item.icon"
                />
                <span>{{ item.title }}</span>
                <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
              </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                <template
                  v-for="subItem in item.items"
                  :key="subItem.title"
                >
                  <SidebarMenuSubItem v-if="$can(subItem.action)">
                    <SidebarMenuSubButton
                      :is-active="$page.url.replace(/\?.*$/, '').includes(subItem.url)"
                      as-child
                    >
                      <a :href="subItem.url">
                        <span>{{ subItem.title }}</span>
                      </a>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </template>
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>

        <SidebarMenuItem v-else-if="canViewNavMenuGroup(item)">
          <SidebarMenuButton
            as-child
            :is-active="item.url === $page.url"
          >
            <a
              :href="item.url"
              :target="item.target"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
