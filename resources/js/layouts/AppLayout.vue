<script setup>
import AppSidebar from "@/components/AppSidebar.vue";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/components/ui/sidebar';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { AlertCircle } from 'lucide-vue-next'
import ability from '@/lib/acl/ability'

const props = defineProps({
  // Share Data
  appName: {
    type: String,
    default: () => import.meta.env.VITE_APP_NAME,
  },
  version: String,
  auth: null,
})

const permissions = props.auth.abilities?.map(permission => typeof permission === 'string' ? { action: permission } : permission)

if (permissions) ability.update(permissions)
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex sticky top-0 bg-background h-16 shrink-0 items-center gap-2 border-b px-4 z-10">
        <SidebarTrigger class="-ml-1" />
        <Separator
          orientation="vertical"
          class="mr-2 h-4"
        />
        <portal-target name="pageTitle" />
      </header>

      <div class="@container/main flex flex-1 flex-col gap-2 p-3">
        <Alert
          v-if="Object.keys($page.props.errors).length"
          variant="destructive"
        >
          <AlertCircle class="w-4 h-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription
            v-for="error in $page.props.errors"
            :key="error"
          >
            {{ error }}
          </AlertDescription>
        </Alert>

        <slot />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
