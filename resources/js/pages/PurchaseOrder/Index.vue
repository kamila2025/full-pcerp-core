<script setup>
import { debounce } from 'lodash'
import { ref, watch, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { SmartTable } from '@/components/app/table'
import { Plus, ShoppingCart, RotateCcw, Download } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

const props = defineProps({
  purchaseOrders: Object,
  locations: Object,
  status: Object,
  arrivalStatus: Object,
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
    header: '進貨單號碼',
    cell: ({ row }) => h('div', { innerHTML: `${row.original.order_number}<br>${row.original.custom_number}` }),
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
    accessorKey: 'type',
    header: '種類',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'status',
    header: '貨單狀態',
  },
  {
    accessorKey: 'arrival_status',
    header: '到貨狀態',
  },
  {
    accessorKey: 'items_sum_quantity',
    header: '預期數量',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'total_amount',
    header: '預期成本',
    class: 'text-nowrap',
    cell: ({ row }) => 'NT$ ' + row.original.total_amount.toLocaleString(),
  },
  {
    accessorKey: 'scheduled_time',
    header: '預定到貨日期',
    class: 'text-nowrap',
    cell: ({ row }) => {
      if (!row.original.scheduled_time) return '-'
      return moment(row.original.scheduled_time).format('YYYY/MM/DD')
    },
  },
]

// FilterPanel
const refFilter = ref(null)

// Filter
const filterFields = [
  {
    key: 'created_at',
    label: '建立日期',
    type: 'date',
    queryMode: 'search',
    defaultValue: ['', moment().endOf('day').unix()],
  },
  {
    key: 'search_field',
    type: 'search-combo',
    label: '搜尋',
    queryMode: 'search',
    defaultValue: 'items.order.order_number',
    options: [
      { value: 'items.order.order_number', label: '拋轉單號碼' },
    ]
  },
  {
    key: 'location_id',
    label: '進貨地點',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.locations).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'status',
    label: '貨單狀態',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.status).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'arrival_status',
    label: '到貨狀態',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.arrivalStatus).map(([value, label]) => ({
      value,
      label
    }))
  },
]

// 初始化 filters
const filters = ref({
  'search_field': props.params?.['search_field'] ?? filterFields.find(field => field.key === 'search_field')?.defaultValue,
  'items.order.order_number': props.params?.['items.order.order_number'] || filterFields.find(field => field.key === 'items.order.order_number')?.defaultValue,
  'created_at': props.params?.['created_at']?.split(',') || filterFields.find(field => field.key === 'created_at')?.defaultValue,
  'location_id': props.params?.['location_id']?.split(',') || filterFields.find(field => field.key === 'location_id')?.defaultValue,
  'status': props.params?.['status']?.split(',') || filterFields.find(field => field.key === 'status')?.defaultValue,
  'arrival_status': props.params?.['arrival_status']?.split(',') || filterFields.find(field => field.key === 'arrival_status')?.defaultValue,
})

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.purchaseOrders.path, { ...finalQuery, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)

// 匯出功能
const exportOrders = () => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))
  const queryString = new URLSearchParams(finalQuery).toString()
  window.open(`/purchase-orders/export?${queryString}`, '_blank')
}

// 監聽 filters 變化
watch(filters, () => fetchData(1), { deep: true })
</script>

<template>
  <AppHeader
    tag="portal"
    title="進貨單"
  >
    <template #actions>
      <Button
        v-if="$can('manage')"
        variant="outline"
        size="icon"
        @click="exportOrders"
        title="匯出訂單"
      >
        <Download />
      </Button>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            type="button"
            variant="outline"
            size="icon"
          >
            <Plus class="w-5 h-5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem @click="router.get(`/purchase-orders/create`, { type: 'purchase' })">
            <ShoppingCart class="mr-2 h-4 w-4" />
            進貨單
          </DropdownMenuItem>
          <DropdownMenuItem @click="router.get(`/purchase-orders/create`, { type: 'return' })">
            <RotateCcw class="mr-2 h-4 w-4" />
            退貨單
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>
  </AppHeader>

  <FilterPanel
    ref="refFilter"
    v-model="filters"
    :fields="JSON.parse(JSON.stringify(filterFields))"
  />

  <SmartTable
    :columns="tableColumns"
    :rows="purchaseOrders.data"
    :per-page="purchaseOrders.per_page"
    :page="purchaseOrders.current_page"
    :total="purchaseOrders.total"
    @on-page-change="page => fetchData(page)"
  >
    <template #cell(order_number)="data">
      <TextLink
        :href="`${purchaseOrders.path}/${data.row.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.value }}
      </TextLink>
      <div class="text-xs text-gray-500">
        {{ data.row.custom_number }}
        {{ data.row.remark ? `(${data.row.remark})` : '' }}
      </div>
    </template>

    <template #cell(type)="data">
      <Badge
        :variant="data.value === 'purchase' ? 'default' : 'destructive'"
        class="whitespace-nowrap"
      >
        {{ $t(`purchase.type.${data.value}`) || data.value }}
      </Badge>
    </template>

    <template #cell(status)="data">
      <Badge
        :variant="data.value === 'cancelled' && 'secondary' || data.value === 'completed' && 'outline' || 'default'"
        class="whitespace-nowrap"
      >
        {{ $t(`purchase.status.${data.value}`) || data.value }}
      </Badge>
    </template>

    <template #cell(arrival_status)="data">
      <Badge
        :variant="data.value === 'all_returned' && 'secondary' || data.value === 'all_received' && 'outline' || 'default'"
        class="whitespace-nowrap"
      >
        {{ $t(`purchase.arrival_status.${data.value}`) || data.value }}
      </Badge>
    </template>
  </SmartTable>
</template>
