<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { SmartTable } from '@/components/app/table'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  filters: Object,
  orders: Object,
})

const queryString = new URLSearchParams(props.filters).toString()

// Column
const tableColumns = [
  {
    accessorKey: 'order_number',
    header: '訂單編號',
  },
  {
    accessorKey: 'created_at',
    header: '訂單日期',
    cell: info => moment(info.getValue()).format('YYYY-MM-DD hh:mm'),
  },
  {
    accessorKey: 'customer.name',
    header: '客戶姓名',
    cell: info => info.getValue() || '',
  },
  {
    accessorKey: 'amount',
    header: '總銷售額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'cost_price',
    header: '總成本',
    cell: info => {
      const order = info.row.original

      const totalCost = Number(order.items_sum_cost_price) + Number(order.transactions_sum_fee) + Number(order.total_shipping)

      return totalCost.toLocaleString()
    },
  },
  {
    accessorKey: 'profit',
    header: '毛利潤',
    class: 'text-right',
    cell: info => {
      const order = info.row.original

      const totalProfit = Number(order.amount) - Number(order.items_sum_cost_price) - Number(order.transactions_sum_fee) - Number(order.total_shipping)

      return totalProfit.toLocaleString()
    },
  },
]
</script>

<template>
  <AppHeader
    tag="portal"
    title="平台銷售額"
    is-back
    @back-action="router.get(`/analytics?${queryString}`)"
  >
  </AppHeader>

  <div class="text-sm text-gray-600">
    日期範圍：{{ moment.unix(filters.start_date).format('YYYY-MM-DD') }} ~ {{ moment.unix(filters.end_date).format('YYYY-MM-DD') }}
  </div>

  <SmartTable
    :columns="tableColumns"
    :rows="orders.data"
    :per-page="orders.per_page"
    :page="orders.current_page"
    :total="orders.total"
    @on-page-change="page => router.get(orders.path, { page }, { preserveState: true })"
  >
    <template #cell(order_number)="data">
      <TextLink
        :href="`/orders/${data.row.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.value }}
      </TextLink>
    </template>
  </SmartTable>
</template>
