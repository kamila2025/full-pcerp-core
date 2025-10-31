<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { SmartTable } from '@/components/app/table'
import { Ellipsis } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  filters: Object,
  sales: Object,
})

// Column
const tableColumns = [
  {
    accessorKey: 'name',
    header: '銷售員',
  },
  {
    accessorKey: 'orders_count',
    header: '訂單',
  },
  {
    accessorKey: 'total_amount',
    header: '銷售額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'total_cost_price',
    header: '總成本',
    cell: info => {
      const user = info.row.original

      const totalCost = Number(user.total_cost_price) + Number(user.total_shipping) + Number(user.total_fee)

      return totalCost.toLocaleString()
    },
  },
  {
    accessorKey: 'profit_rate',
    header: '利潤率',
    cell: info => {
      const user = info.row.original

      const totalProfit = Number(user.total_amount) - Number(user.total_cost_price) - Number(user.total_shipping) - Number(user.total_fee) - Number(user.total_tax)

      const profitRate = totalProfit > 0 ? (totalProfit / Number(user.total_amount)) * 100 : 0

      return profitRate.toFixed(2) + '%'
    },
  },
  {
    accessorKey: 'profit',
    header: '毛利潤',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const user = info.row.original

      const totalProfit = Number(user.total_amount) - Number(user.total_cost_price) - Number(user.total_shipping) - Number(user.total_fee) - Number(user.total_tax)

      return totalProfit.toLocaleString()
    },
  },
]

const queryString = new URLSearchParams(props.filters).toString()

const exportExcel = () => {
  window.location.href = `/analytics/sales/export?${queryString}`
}
</script>

<template>
  <AppHeader
    tag="portal"
    title="門市銷售員銷售總額"
    is-back
    @back-action="router.get(`/analytics?${queryString}`)"
  >
   <template #actions>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="icon"
          >
            <Ellipsis class="w-5 h-5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem @click="exportExcel">匯出 Excel</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
   </template>
  </AppHeader>

  <div class="text-sm text-gray-600">
    日期範圍：{{ moment.unix(filters.start_date).format('YYYY-MM-DD') }} ~ {{ moment.unix(filters.end_date).format('YYYY-MM-DD') }}
  </div>

  <SmartTable
    :columns="tableColumns"
    :rows="sales.data"
    :per-page="sales.per_page"
    :page="sales.current_page"
    :total="sales.total"
    @on-page-change="page => router.get(sales.path, { page }, { preserveState: true })"
  />
</template>
