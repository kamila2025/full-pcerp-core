<script setup>
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { SmartTable } from '@/components/app/table'
import { Pencil, X, Plus } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const props = defineProps({
  users: Object,
  errors: Object,
  //
  auth: Object,
})

// Column
const tableColumns = [
  {
    accessorKey: 'name',
    header: 'Name',
  },
  {
    accessorKey: 'email',
    header: 'Eamil',
  },
  {
    accessorKey: 'action',
    header: '',
    class: 'text-right',
  },
]

const onDeleting = row => {
  if (confirm('確定要刪除此用戶嗎？')) {
    router.delete(`${props.users.path}/${row.id}`, {
      prefetch: true,
      onPrefetched: () => router.reload(),
    })
  }
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="$t('Users')"
  >
    <template #actions>
      <Button
        variant="outline"
        size="icon"
        @click="router.get(`${users.path}/create`)"
      >
        <Plus />
        <span class="sr-only">Toggle Sidebar</span>
      </Button>
    </template>
  </AppHeader>

  <SmartTable
    :columns="tableColumns"
    :rows="users.data"
    :per-page="users.per_page"
    :page="users.current_page"
    :total="users.total"
    @onPageChange="page => router.get(props.users.path, { page }, { preserveState: true })"
  >
    <template #cell(action)="data">
      <Button
        :disabled="auth.user.id === data.row.id"
        variant="ghost"
        size="icon"
        class="text text-right"
        @click="router.get(`${props.users.path}/${data.row.id}/edit`)"
      >
        <Pencil class="w-4 h-4 text-green-500" />
      </Button>

      <Button
        :disabled="auth.user.id === data.row.id"
        variant="ghost"
        size="icon"
        class="text text-right"
        @click="onDeleting(data.row)"
      >
        <X class="w-4 h-4 text-red-500" />
      </Button>
    </template>
  </SmartTable>
</template>
