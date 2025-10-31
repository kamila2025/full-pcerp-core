<script setup>
import { debounce } from 'lodash'
import { h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { SmartTable } from '@/components/app/table'
import { Plus } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import moment from 'moment'

const { t } = useI18n()

const props = defineProps({
  adjustments: Object,
  //
  params: Object,
  errors: Object,
  auth: Object,
  version: String,
})

// Column
const tableColumns = [
  {
    accessorKey: 'order_number',
    header: '盤點單號碼',
  },
  {
    accessorKey: 'created_at',
    header: '建立日期',
    cell: ({ row }) => {
      const date = moment(row.original.created_at).format('YYYY/MM/DD')
      const time = moment(row.original.created_at).format('HH:mm:ss')
      return h('div', { innerHTML: `${date}<br>${time}` })
    },
  },
  {
    accessorKey: 'start_at',
    header: '盤點開始時間',
    cell: ({ row }) => {
      if (!row.original.start_at) return '-'
      const date = moment(row.original.start_at).format('YYYY/MM/DD')
      const time = moment(row.original.start_at).format('HH:mm:ss')
      return h('div', { innerHTML: `${date}<br>${time}` })
    },
  },
  {
    accessorKey: 'location.name',
    header: '分店',
  },
  {
    accessorKey: 'status',
    header: '盤點狀態',
    cell: ({ row }) => t(`adjustment.status.${row.original.status}`),
  },
  {
    accessorKey: 'result',
    header: '盤點結果',
    cell: ({ row }) => t(`adjustment.result.${row.original.result}`),
  },
]

const onDeleting = row => {
  if (confirm('確定要刪除此用戶嗎？')) {
    router.delete(`${props.customers.path}/${row.id}`, {
      prefetch: true,
      onPrefetched: () => router.reload(),
    })
  }
}

// 抓取資料
const fetchData = debounce((page = 1) => {
  router.get(props.adjustments.path, { page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)
</script>

<template>
  <AppHeader
    tag="portal"
    title="盤點單"
  >
    <template #actions>
      <Button
        variant="outline"
        size="icon"
        @click="router.get(`/adjustment-orders/create`)"
      >
        <Plus />
      </Button>
    </template>
  </AppHeader>

  <SmartTable
    :columns="tableColumns"
    :rows="adjustments.data"
    :per-page="adjustments.per_page"
    :page="adjustments.current_page"
    :total="adjustments.total"
    @on-page-change="page => fetchData(page)"
  >
    <template #cell(order_number)="data">
      <TextLink
        :href="`${adjustments.path}/${data.row.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.value }}
      </TextLink>
    </template>
  </SmartTable>
</template>
