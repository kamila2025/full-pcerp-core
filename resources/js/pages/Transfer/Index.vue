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
  transferOrders: Object,
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
    header: '調撥單號碼',
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
    accessorKey: 'from_location.name',
    header: '原始分店',
  },
  {
    accessorKey: 'to_location.name',
    header: '目的分店',
  },
  {
    accessorKey: 'status',
    header: '調撥狀態',
    cell: ({ row }) => t(`transfer.status.${row.original.status}`),
  },
  {
    accessorKey: 'arrival_status',
    header: '到貨狀態',
    cell: ({ row }) => t(`transfer.arrival_status.${row.original.arrival_status}`),
  },
  {
    accessorKey: 'note',
    header: '備註',
  },
  {
    accessorKey: 'delivery_date',
    header: '送貨日期',
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
  router.get(transferOrders.path, { page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)
</script>

<template>
  <AppHeader
    tag="portal"
    title="調撥單"
  >
    <template #actions>
      <Button
        variant="outline"
        size="icon"
        @click="router.get(`/transfer-orders/create`)"
      >
        <Plus />
      </Button>
    </template>
  </AppHeader>

  <SmartTable
    :columns="tableColumns"
    :rows="transferOrders.data"
    :per-page="transferOrders.per_page"
    :page="transferOrders.current_page"
    :total="transferOrders.total"
    @on-page-change="page => fetchData(page)"
  >
    <template #cell(order_number)="data">
      <TextLink
        :href="`${transferOrders.path}/${data.row.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.value }}
      </TextLink>
    </template>
  </SmartTable>
</template>
