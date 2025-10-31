<script setup>
import { debounce } from 'lodash'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { SmartTable } from '@/components/app/table'
import { X, Plus, FileSignature, Receipt, ScrollText, FileText, Download } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

const props = defineProps({
  orders: Object,
  templates: Array,
  users: Object,
  locations: Object,
  fulfillmentStatus: Object,
  financialStatus: Object,
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
    header: '訂單編號',
    class: 'hidden sm:table-cell min-w-[200px]',
  },
  {
    accessorKey: 'created_at',
    header: '訂單日期',
    class: 'hidden sm:table-cell',
  },
  {
    accessorKey: 'customer',
    header: '客戶姓名',
    class: 'hidden sm:table-cell',
  },
  {
    accessorKey: 'financial_status',
    header: '付款狀態',
    class: 'hidden sm:table-cell',
  },
  {
    accessorKey: 'fulfillment_status',
    header: '出貨狀態',
    class: 'hidden sm:table-cell',
  },
  {
    accessorKey: 'amount',
    header: '總金額',
    class: 'hidden sm:table-cell text-right',
  },
  {
    accessorKey: 'mobile',
    header: '',
    class: 'table-cell sm:hidden',
  },
  {
    accessorKey: 'action',
    header: '',
    class: 'text-right w-0 whitespace-nowrap',
  },
]

const mapStatusVariant = (status) => {
  switch (status) {
    case 'paid':
    case 'fulfilled':
    case 'pickup':
      return 'secondary'
    case 'draft':
      return 'warning'
    case 'archived':
      return 'outline'
    default:
      return 'default'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'draft':
      return '草稿'
    case 'archived':
      return '已封存'
    case 'open':
      return '處理中'
    default:
      return status
  }
}

const onDeleting = row => {
  if (confirm('確定要刪除嗎？')) {
    router.delete(`${props.orders.path}/${row.id}`)
  }
}

// FilterPanel
const refFilter = ref(null)

// Filter
const filterFields = [
  {
    key: 'search_field',
    type: 'search-combo',
    label: '搜尋',
    queryMode: 'search',
    defaultValue: 'order_number',
    options: [
      { value: 'order_number', label: '訂單編號' },
      { value: 'customer.name', label: '客戶姓名' },
      { value: 'items.product.name', label: '商品名稱' },
      { value: 'items.product_name', label: '客製化商品' },
    ]
  },
  {
    key: 'created_at',
    label: '建立日期',
    type: 'date',
    queryMode: 'search',
    defaultValue: ['', moment().endOf('day').unix()],
  },
  {
    key: 'customer_id',
    label: '客戶',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: null,
  },
  {
    key: 'attribution_user_id',
    label: '銷售員',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.users).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'location_id',
    label: '地點',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.locations).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'fulfillment_status',
    label: '出貨',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.fulfillmentStatus).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'financial_status',
    label: '付款',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.financialStatus).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'status',
    label: '狀態',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: [
      { value: 'draft', label: '草稿' },
      { value: 'open', label: '處理中' },
      { value: 'archived', label: '已封存' },
      { value: 'deleted', label: '已刪除' },
    ],
  },
]

// 初始化 filters
const filters = ref({
  'customer_id': props.params?.['customer_id']?.split(',') || filterFields.find(field => field.key === 'customer_id')?.defaultValue,
  'attribution_user_id': props.params?.['attribution_user_id']?.split(',') || filterFields.find(field => field.key === 'attribution_user_id')?.defaultValue,
  'location_id': props.params?.['location_id']?.split(',') || filterFields.find(field => field.key === 'location_id')?.defaultValue,
  'financial_status': props.params?.['financial_status']?.split(',') || filterFields.find(field => field.key === 'financial_status')?.defaultValue,
  'fulfillment_status': props.params?.['fulfillment_status']?.split(',') || filterFields.find(field => field.key === 'fulfillment_status')?.defaultValue,
  'order_number': props.params?.['order_number'] || filterFields.find(field => field.key === 'order_number')?.defaultValue,
  'customer.name': props.params?.['customer.name'] || filterFields.find(field => field.key === 'customer.name')?.defaultValue,
  'items.product.name': props.params?.['items.product.name'] || filterFields.find(field => field.key === 'items.product.name')?.defaultValue,
  'items.product_name': props.params?.['items.product_name'] || filterFields.find(field => field.key === 'items.product_name')?.defaultValue,
  'search_field': props.params?.['search_field'] ?? filterFields.find(field => field.key === 'search_field')?.defaultValue,
  'created_at': props.params?.['created_at']?.split(',') || filterFields.find(field => field.key === 'created_at')?.defaultValue,
  'status': props.params?.['status']?.split(',') || filterFields.find(field => field.key === 'status')?.defaultValue,
})

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.orders.path, { ...finalQuery, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)

// 匯出功能
const exportOrders = () => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))
  const queryString = new URLSearchParams(finalQuery).toString()
  window.open(`/orders/export?${queryString}`, '_blank')
}

// 監聽 filters 變化
watch(filters, () => fetchData(1), { deep: true })
</script>

<template>
  <AppHeader
    tag="portal"
    :title="`所有訂單`"
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
      <Button
        variant="outline"
        size="icon"
        @click="router.get(`${orders.path}/create`)"
      >
        <Plus />
      </Button>
    </template>
  </AppHeader>

  <!-- 新增按鈕列 -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1">
    <button
      v-for="(template, index) in templates"
      :key="index"
      class="flex items-center gap-3 p-4 border rounded-xl shadow-sm hover:bg-accent transition"
      @click="router.get(`${orders.path}/create?template_id=${template.id}`)"
    >
      <div class="flex items-center justify-center w-10 h-10 bg-muted rounded-md">
        {{ template.icon }}
      </div>
      <div class="text-left">
        <h3 class="text-sm font-semibold">{{ template.name }}</h3>
        <p class="text-xs text-muted-foreground">{{ template.description }}</p>
      </div>
    </button>
  </div>

  <FilterPanel
    ref="refFilter"
    v-model="filters"
    :fields="JSON.parse(JSON.stringify(filterFields))"
  />

  <SmartTable
    :columns="tableColumns"
    :rows="orders.data"
    :per-page="orders.per_page"
    :page="orders.current_page"
    :total="orders.total"
    thead-class="hidden sm:table-row-group"
    :row-class="row => {
      if (row.original.status === 'archived') return 'opacity-50 !border-l-4 border-l-gray-300 hover:opacity-100 transition-opacity'
      if (row.original.status === 'draft') return 'bg-yellow-50/30 !border-l-4 border-l-yellow-400'
      return ''
    }"
    @on-page-change="page => fetchData(page)"
  >
    <template #cell(order_number)="data">
      <div class="flex items-center space-x-1">
        <FileSignature
          v-if="data.row.source_type === 'admin_panel'"
          class="w-6 h-6 text-gray-500"
        />
        <Receipt
          v-else-if="data.row.source_type === 'pos'"
          class="w-6 h-6 text-gray-500"
        />
        <ScrollText
          v-else-if="data.row.source_type === 'erp'"
          class="w-6 h-6 text-gray-500"
        />
        <div class="flex flex-col">
          <TextLink
            :href="`/orders/${data.row.id}/edit`"
            class="text-blue-600 hover:text-blue-400 no-underline flex items-center gap-2"
          >
            <span>{{ data.value }}</span>
            <div
              class="w-2 h-2 rounded-full"
              :class="{ 'bg-yellow-400': data.row.status === 'draft', 'bg-gray-400': data.row.status === 'archived', 'bg-green-400': data.row.status === 'open' }"
            />
          </TextLink>
          <span class="text-xs text-yellow-600">{{ data.row.attribution_user?.name }}</span>
        </div>
      </div>
    </template>

    <template #cell(created_at)="data">
      <span class="whitespace-nowrap">{{ moment(data.value).fromNow() }}</span>
    </template>

    <template #cell(customer)="data">
      <TextLink
        :href="`/customers/${data.row.customer_id}/edit`"
        class="text-blue-600 hover:text-blue-400 no-underline"
      >
        <span class="whitespace-nowrap">{{ data.row.customer?.name }}</span>
      </TextLink>
    </template>

    <template #cell(financial_status)="data">
      <Badge
        :variant="mapStatusVariant(data.value)"
        class="whitespace-nowrap"
      >
        {{ $t(`order.payment.${data.value}`) || data.value }}
      </Badge>
    </template>

    <template #cell(fulfillment_status)="data">
      <Badge
        :variant="mapStatusVariant(data.value)"
        class="whitespace-nowrap"
      >
        {{ $t(`order.fulfillment.${data.value}`) || data.value }}
      </Badge>
    </template>

    <template #cell(amount)="data">
      {{ data.value.toLocaleString() }}
      <span class="text-gray-500">TWD</span>
    </template>

    <template #cell(mobile)="data">
      <TextLink
        :href="`${orders.path}/${data.row.id}/edit`"
        class="no-underline flex items-center"
      >
        <div class="flex items-center mr-1">
          <FileSignature
            v-if="data.row.source_type === 'admin_panel'"
            class="w-7 h-7 text-gray-500"
          />
          <Receipt
            v-else-if="data.row.source_type === 'pos'"
            class="w-7 h-7 text-gray-500"
          />
          <ScrollText
            v-else-if="data.row.source_type === 'erp'"
            class="w-7 h-7 text-gray-500"
          />
        </div>
        <div class="flex-auto">
          <div class="flex items-start">
            <div class="text-blue-600 flex items-center gap-2">
              <span>{{ data.row.order_number }}</span>
              <div
                class="w-2 h-2 rounded-full"
                :class="{ 'bg-yellow-400': data.row.status === 'draft', 'bg-gray-400': data.row.status === 'archived', 'bg-green-400': data.row.status === 'open' }"
              />
            </div>
            <span class="ml-auto font-medium">
              {{ data.row.amount?.toLocaleString() }}
              <span class="text-gray-500">TWD</span>
            </span>
          </div>
          <div class="flex items-start">
            <div class="flex flex-col">
              <span class="text-gray-500 text-xs">{{ moment(data.row.created_at).fromNow() }}</span>
              <div class="flex items-center space-x-1">
                <span class="text-gray-500 text-xs">{{ data.row.customer?.name }}</span>
                <small class="text-yellow-500">{{ data.row.attribution_user?.name }}</small>
              </div>
            </div>
            <span class="ml-auto font-medium space-x-1">
              <Badge :variant="mapStatusVariant(data.row.financial_status)">
                {{ $t(`order.payment.${data.row.financial_status}`) || data.row.financial_status }}
              </Badge>
              <Badge :variant="mapStatusVariant(data.row.fulfillment_status)">
                {{ $t(`order.fulfillment.${data.row.fulfillment_status}`) || data.row.fulfillment_status }}
              </Badge>
            </span>
          </div>
        </div>
      </TextLink>
    </template>

    <template #cell(action)="data">
      <Button
        v-if="data.row.status !== 'archived'"
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
