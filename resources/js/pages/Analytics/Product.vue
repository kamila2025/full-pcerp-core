<script setup>
import { debounce } from 'lodash'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { SmartTable } from '@/components/app/table'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  users: Object,
  locations: Object,
  orders: Object,
  //
  params: Object,
})

// Column
const tableColumns = [
  {
    accessorKey: 'product_name',
    header: '商品',
    cell: info => info.getValue() || '',
  },
  {
    accessorKey: 'nuit_sold',
    header: '銷售數量',
    cell: info => info.getValue() || '',
  },
  {
    accessorKey: 'total_sales',
    header: '總銷售額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'total_cost',
    header: '總成本',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'gross_margin',
    header: '毛利率',
    cell: info => info.getValue() + '%',
  },
  {
    accessorKey: 'gross_profit',
    header: '毛利潤',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
]

// FilterPanel
const refFilter = ref(null)

// Filter
const filterFields = [
  {
    key: 'search_field',
    type: 'search-combo',
    label: '搜尋',
    queryMode: 'query',
    defaultValue: 'product_name',
    options: [
      { value: 'product_name', label: '商品名稱' },
    ]
  },
  {
    key: 'attribution_user_id',
    label: '銷售員',
    type: 'multi-select',
    queryMode: 'query',
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
    queryMode: 'query',
    defaultValue: [],
    options: Object.entries(props.locations).map(([value, label]) => ({
      value,
      label
    }))
  },
  // {
  //   key: 'created_at',
  //   label: '日期範圍',
  //   type: 'date',
  //   queryMode: 'query',
  //   defaultValue: [moment.unix(props.filters.start_date).startOf('day').unix(), moment.unix(props.filters.end_date).endOf('day').unix()],
  // },
]


// 初始化 filters
const filters = ref({
  'attribution_user_id': props.params?.['attribution_user_id']?.split(',') || filterFields.find(field => field.key === 'attribution_user_id')?.defaultValue,
  'location_id': props.params?.['location_id']?.split(',') || filterFields.find(field => field.key === 'location_id')?.defaultValue,
  'search_field': props.params?.['search_field'] ?? filterFields.find(field => field.key === 'search_field')?.defaultValue,
  // 'created_at': props.params?.['created_at']?.split(',') || filterFields.find(field => field.key === 'created_at')?.defaultValue,
})

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.orders.path, { ...finalQuery, page, start_date: props.params.start_date, end_date: props.params.end_date }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 800)

// 監聽 filters 變化
watch(filters, () => fetchData(1), { deep: true })
</script>

<template>
  <AppHeader
    tag="portal"
    title="商品銷售額"
    is-back
    @back-action="router.get(`/analytics?start_date=${params.start_date}&end_date=${params.end_date}`)"
  >
  </AppHeader>

  <div class="text-sm text-gray-600">
    日期範圍：{{ moment.unix(params.start_date).format('YYYY-MM-DD') }} ~ {{ moment.unix(params.end_date).format('YYYY-MM-DD') }}
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
    @on-page-change="page => router.get(orders.path, { page }, { preserveState: true })"
  >
    <template #cell(product_name)="data">
      <!-- <TextLink
        :href="`/orders/${data.row.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.value }}
      </TextLink> -->
      {{ data.value }}
      <!-- {{ data }} -->
    </template>
  </SmartTable>
</template>
