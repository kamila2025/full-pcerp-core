<script setup>
import { debounce } from 'lodash'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { SmartTable } from '@/components/app/table'
import { X } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

const props = defineProps({
  customers: Object,
  locations: Object,
  users: Object,
  //
  params: Object,
  errors: Object,
  auth: Object,
  version: String,
})

// Column
const tableColumns = [
  {
    accessorKey: 'name',
    header: '姓名',
  },
  {
    accessorKey: 'email',
    header: '信箱',
  },
  {
    accessorKey: 'phone',
    header: '手機',
  },
  {
    accessorKey: 'created_at',
    header: '註冊日',
    cell: ({ row }) => {
      const date = moment(row.getValue('created_at'));
      const daysAgo = moment().diff(date, 'days');
      return daysAgo > 7 ? date.format('YYYY-MM-DD') : date.fromNow();
    },
  },
  {
    accessorKey: 'status',
    header: '狀態',
  },
  {
    accessorKey: 'orders_sum_amount',
    header: '總消費',
    cell: ({ row }) => {
      return Number(row.getValue('orders_sum_amount')).toLocaleString() + ' TWD'
    },
  },
  {
    accessorKey: 'action',
    header: '',
    class: 'text-right w-0 whitespace-nowrap',
  },
]

const onDeleting = row => {
  if (confirm('確定要刪除此用戶嗎？')) {
    router.delete(`${props.customers.path}/${row.id}`)
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
    defaultValue: 'name',
    options: [
      { value: 'name', label: '姓名' },
      { value: 'email', label: '信箱' },
      { value: 'phone', label: '手機' },
    ]
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
    key: 'attribution_location_id',
    label: '地點',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.locations).map(([value, label]) => ({
      value,
      label
    }))
  },
]

// 初始化 filters
const filters = ref({
  'search_field': props.params?.['search_field'] || filterFields.find(field => field.key === 'search_field')?.defaultValue,
  'name': props.params?.['name'] || filterFields.find(field => field.key === 'name')?.defaultValue,
  'email': props.params?.['email'] || filterFields.find(field => field.key === 'email')?.defaultValue,
  'phone': props.params?.['phone'] || filterFields.find(field => field.key === 'phone')?.defaultValue,
  'attribution_user_id': props.params?.['attribution_user_id']?.split(',') || filterFields.find(field => field.key === 'attribution_user_id')?.defaultValue,
  'attribution_location_id': props.params?.['attribution_location_id']?.split(',') || filterFields.find(field => field.key === 'attribution_location_id')?.defaultValue,
})

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.customers.path, { ...finalQuery, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)

// 監聽 filters 變化
watch(filters, () => fetchData(1), { deep: true })
</script>

<template>
  <AppHeader
    tag="portal"
    title="客戶管理"
  >
    <!-- <template #actions>
      <Button
        variant="outline"
        size="icon"
        @click="router.get(`${customers.path}/create`)"
      >
        <Plus />
        <span class="sr-only">Toggle Sidebar</span>
      </Button>
    </template> -->
  </AppHeader>

  <FilterPanel
    ref="refFilter"
    v-model="filters"
    :fields="JSON.parse(JSON.stringify(filterFields))"
  />

  <SmartTable
    :columns="tableColumns"
    :rows="customers.data"
    :per-page="customers.per_page"
    :page="customers.current_page"
    :total="customers.total"
    @onPageChange="page => router.get(customers.path, { page }, { preserveState: true })"
  >
    <template #cell(name)="data">
      <div class="flex flex-col">
        <TextLink
          :href="`${customers.path}/${data.row.id}/edit`"
          class="text-blue-600 no-underline"
        >
          {{ data.value }}
        </TextLink>
        <span class="text-xs text-yellow-600">{{ data.row.attribution_user?.name }}</span>
      </div>
    </template>

    <template #cell(status)="data">
      <Badge
        variant="outline"
        class="whitespace-nowrap"
      >
        {{ $t('customer.status.' + data.value) }}
      </Badge>
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
