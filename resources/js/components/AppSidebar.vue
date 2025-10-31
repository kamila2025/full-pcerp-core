<script setup>
import NavMain from '@/components/NavMain.vue'
import NavFooter from '@/components/NavFooter.vue'
import NavUser from '@/components/NavUser.vue'
import TeamSwitcher from '@/components/TeamSwitcher.vue'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'
import {
  AudioWaveform,
  BookOpen,
  Bot,
  Command,
  Frame,
  GalleryVerticalEnd,
  Map,
  PieChart,
  Settings2,
  SquareTerminal,
} from 'lucide-vue-next'
import navMenuItems from '@/navigation'

const props = withDefaults(defineProps(), {
  collapsible: 'icon',
})

// This is sample data.
const data = {
  teams: [
    // {
    //   name: 'Acme Inc',
    //   logo: GalleryVerticalEnd,
    //   plan: 'Enterprise',
    // },
    // {
    //   name: 'Acme Corp.',
    //   logo: AudioWaveform,
    //   plan: 'Startup',
    // },
    // {
    //   name: 'Evil Corp.',
    //   logo: Command,
    //   plan: 'Free',
    // },
  ],
  ...navMenuItems,
}
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <TeamSwitcher :teams="data.teams" />
    </SidebarHeader>
    <SidebarContent>
      <NavMain
        v-if="data.navMain"
        :items="data.navMain"
      />
      <NavFooter
        v-if="data.navSecondary"
        :items="data.navSecondary"
        class="mt-auto"
      />
    </SidebarContent>
    <SidebarFooter>
      <NavUser :user="$page.props?.auth?.user || {}" />
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
