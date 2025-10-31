<script setup>
import { debounce } from 'lodash'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { SmartTable } from '@/components/app/table'
import AppHeader from '@/components/AppHeader.vue'
import moment from 'moment'
const props = defineProps({
  locations: Array,
  product: Object,
  logs: Object,
  //
  params: Object,
})

// Column
const tableColumns = [
  {
    accessorKey: 'created_at',
    header: '日期',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'event',
    header: '活動',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'variant_name',
    header: '規格',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'location.name',
    header: '倉庫',
    class: 'text-nowrap',
  },
  {
    accessorKey: 'in',
    header: '入帳',
    class: 'text-nowrap',
    cell: ({ row }) => row.original.quantity_after - row.original.quantity_before > 0 ? `+${row.original.quantity_after - row.original.quantity_before}` : '',
  },
  {
    accessorKey: 'out',
    header: '支出',
    class: 'text-nowrap',
    cell: ({ row }) => row.original.quantity_before - row.original.quantity_after > 0 ? `-${row.original.quantity_before - row.original.quantity_after}` : '',
  },
  {
    accessorKey: 'stock',
    header: '庫存',
    class: 'text-nowrap',
    cell: ({ row }) => row.original.quantity_after,
  },
  {
    accessorKey: 'causer',
    header: '操作者',
    class: 'w-0 text-wrap',
  },
]

// 抓取資料
const fetchData = debounce((params = {}) => {
  router.get(props.logs.path, params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)
</script>

<template>
  <AppHeader
    tag="portal"
    :title="product.name"
    is-back
    @back-action="router.get(params.back_url || `/products/${product.id}/edit`)"
  />

  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">庫存紀錄</h2>

    <div class="flex space-x-2">
      <Select
        :model-value="Number(params.location_id)"
        @update:model-value="fetchData({ product_id: product.id, location_id: $event || undefined })"
      >
        <SelectTrigger>
          <SelectValue placeholder="Select" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="null">全部</SelectItem>
          <SelectItem
            v-for="location in locations"
            :key="location.id"
            :value="location.id"
          >
            {{ location.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  </div>

  <SmartTable
    :columns="tableColumns"
    :rows="logs.data"
    :per-page="logs.per_page"
    :page="logs.current_page"
    :total="logs.total"
    @on-page-change="page => fetchData({ product_id: product.id, location_id: params.location_id, page })"
  >
    <template #cell(created_at)="data">
      <div class="flex flex-col">
        <div>{{ moment(data.row.created_at).format('YYYY-MM-DD') }}</div>
        <div class="text-sm text-gray-500">{{ moment(data.row.created_at).format('HH:mm:ss') }}</div>
      </div>
    </template>

    <template #cell(event)="data">
      <div class="flex flex-col">
        <component :is="data.row.variant ? 'span' : 's'">
          {{ $t(`inventory-log.type.${data.row.type}`) }}
        </component>
        <component
          v-if="['product_transaction', 'product_transaction_edit', 'product_transaction_deleted'].includes(data.row.type)"
          :is="data.row.reference ? 'a' : 's'"
          :href="`/orders/${data.row.reference?.order_id}/edit#order-item-${data.row.reference?.id}`"
          class="text-gray-500"
        >
          {{ data.row.properties.order?.order_number }}
        </component>
        <component
          v-if="['purchase_order_completed', 'purchase_order_cancelled', 'purchase_return_order_completed'].includes(data.row.type)"
          :is="data.row.reference ? 'a' : 's'"
          :href="`/purchase-orders/${data.row.reference?.purchase_order_id}/edit`"
          class="text-gray-500"
        >
          {{ data.row.properties.purchase_order?.order_number }}
        </component>
        <component
          v-if="['inventory_adjustment_corrected'].includes(data.row.type)"
          :is="data.row.reference ? 'a' : 's'"
          :href="`/adjustment-orders/${data.row.reference?.adjustment_order_id}/edit?back_url=${$page.url}#order-item-${data.row.reference?.id}`"
          class="text-gray-500"
        >
          {{ data.row.properties.adjustment_order?.order_number }}
        </component>
      </div>
    </template>

    <template #cell(variant_name)="data">
      {{ data.row.model_data?.variant?.name }}
    </template>

    <template #cell(causer)="data">
      <div>{{ data.row.causer.email }}</div>
      <div>{{ data.row.causer.name }}</div>
    </template>
  </SmartTable>
</template>
