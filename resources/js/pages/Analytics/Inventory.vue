<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { SmartTable } from '@/components/app/table'
import AppHeader from '@/components/AppHeader.vue'
import moment from 'moment'

const props = defineProps({
  inventories: Object,
})


// 定義表格列
const tableColumns = [
  {
    accessorKey: 'product_name',
    header: '商品名稱',
  },
  {
    accessorKey: 'purchase_quantity',
    header: '進貨',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'purchase_return_quantity',
    header: '廠商退貨',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'sales_quantity',
    header: '銷售',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'order_return_quantity',
    header: '訂單退貨',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'adjustment_in_quantity',
    header: '調整進',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'adjustment_out_quantity',
    header: '調整出',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'transfer_in_quantity',
    header: '庫存調入',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'transfer_out_quantity',
    header: '庫存調出',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
  {
    accessorKey: 'current_stock',
    header: '當前庫存',
    cell: info => Number(info.getValue()).toLocaleString(),
    class: 'text-right',
  },
]
</script>

<template>
  <AppHeader
    tag="portal"
    title="庫存分析"
  >
  </AppHeader>

  <!-- <div class="text-sm text-gray-600">
    日期範圍：{{ moment.unix(filters.start_date).format('YYYY-MM-DD') }} ~ {{ moment.unix(filters.end_date).format('YYYY-MM-DD') }}
  </div> -->

  <SmartTable
    :columns="tableColumns"
    :rows="inventories.data"
    :per-page="inventories.per_page"
    :page="inventories.current_page"
    :total="inventories.total"
    table-class="table-fixed"
    @on-page-change="page => router.get(inventories.path, { page }, { preserveState: true })"
  >
    <template #cell(product_name)="data">
      <span>{{ data.row.product_name }}</span>
      <small class="text-muted-foreground ml-1">{{ data.row.variant_name }}</small>
      <!-- <div class="flex items-center">
        <img :src="row.image" alt="Product Image" class="w-10 h-10 rounded-full mr-2">
        <span>{{ row.product_name }}</span>
      </div> -->
    </template>
  </SmartTable>
</template>
