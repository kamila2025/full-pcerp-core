<script setup>
import { ref, computed, watch,h } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { SmartTable } from '@/components/app/table'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Pencil, Check, X, Ellipsis } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  filters: Object,
  orders: Array,
  products: Array,
  customers: Array,
  transactions: Array,
  locations: Array,
  users: Array,
})

const orderTableColumns = [
  {
    accessorKey: 'index',
    header: '#',
    class: 'w-0',
    cell: info => info.row.index + 1,
  },
  {
    accessorKey: 'order_number',
    header: '訂單編號',
    cell: info => info.getValue(),
  },
  {
    accessorKey: 'amount',
    header: '總銷售額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right',
    cell: info => {
      const total = props.orders.reduce((acc, item) => acc + Number(item.amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const productTableColumns = [
  {
    accessorKey: 'index',
    header: '#',
    class: 'w-0',
    cell: info => info.row.index + 1,
  },
  {
    accessorKey: 'name',
    header: '商品',
    cell: info => h('span', { class: ' ' }, info.getValue()),
  },
  {
    accessorKey: 'total_amount',
    header: '銷售總額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const total = props.products.reduce((acc, item) => acc + Number(item.total_amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('total_amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const customerTableColumns = [
  {
    accessorKey: 'customer_source',
    header: '客戶來源',
    cell: info => info.getValue() || '未指定',
  },
  {
    accessorKey: 'orders_sum_amount',
    header: '銷售總額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const total = props.customers.reduce((acc, item) => acc + Number(item.orders_sum_amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('orders_sum_amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const transactionTableColumns = [
  {
    accessorKey: 'gateway_title',
    header: '付款方式',
  },
  {
    accessorKey: 'total_amount',
    header: '總交易額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const total = props.transactions.reduce((acc, item) => acc + Number(item.total_amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('total_amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const locationTableColumns = [
  {
    accessorKey: 'name',
    header: '地址',
  },
  {
    accessorKey: 'total_amount',
    header: '總交易額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const total = props.locations.reduce((acc, item) => acc + Number(item.total_amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('total_amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const userTableColumns = [
  {
    accessorKey: 'name',
    header: '銷售員',
  },
  {
    accessorKey: 'total_amount',
    header: '總銷售額',
    cell: info => Number(info.getValue()).toLocaleString(),
  },
  {
    accessorKey: 'percentage',
    header: '百分比',
    class: 'text-right w-0 whitespace-nowrap',
    cell: info => {
      const total = props.users.reduce((acc, user) => acc + Number(user.total_amount), 0)

      const percentage = total > 0 ? (Number(info.row.getValue('total_amount')) / total) * 100 : 0

      return percentage.toFixed(2) + '%'
    },
  },
]

const startDate = ref(moment.unix(props.filters.start_date || moment().startOf('month').unix()).format('YYYY-MM-DD'))

const endDate = ref(moment.unix(props.filters.end_date || moment().endOf('day').unix()).format('YYYY-MM-DD'))

const startTimestamp = computed(() => moment(startDate.value).startOf('day').unix())

const endTimestamp = computed(() => moment(endDate.value).endOf('day').unix())

const queryString = computed(() => {
  const params = new URLSearchParams(props.filters)
  params.set('start_date', startTimestamp.value)
  params.set('end_date', endTimestamp.value)
  return params.toString()
})

const reloadWithDateRange = () => {
  router.reload({
    data: {
      start_date: startTimestamp.value,
      end_date: endTimestamp.value,
    },
  })
}

watch([startDate, endDate], reloadWithDateRange)
</script>

<template>
  <AppHeader
    tag="portal"
    title="分析報表"
  >
  </AppHeader>

  <div class="flex items-center gap-2">
    <Input
      type="date"
      v-model="startDate"
    />
    <span class="text-gray-500">—</span>
    <Input
      type="date"
      v-model="endDate"
    />
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 space-y-0">
    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">訂單銷售額</h3>
        <Button
          :href="`/analytics/orders?${queryString}`"
          as="a"
          variant="link"
          class="text-blue-600 px-0 h-auto text-sm"
        >
          查看更多
        </Button>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="orderTableColumns"
          :rows="orders"
          :pagination="false"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">商品銷售額</h3>
        <Button
          :href="`/analytics/products?${queryString}`"
          as="a"
          variant="link"
          class="text-blue-600 px-0 h-auto text-sm"
        >
          查看更多
        </Button>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="productTableColumns"
          :rows="products"
          :pagination="false"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">依據客戶來源</h3>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="customerTableColumns"
          :rows="customers"
          :pagination="false"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">依據付款方式</h3>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="transactionTableColumns"
          :rows="transactions"
          :pagination="false"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">依據地址銷售額</h3>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="locationTableColumns"
          :rows="locations"
          :pagination="false"
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="flex flex-row justify-between items-centers">
        <h3 class="text-sm">門市銷售員銷售總額</h3>
        <Button
          :href="`/analytics/sales?${queryString}`"
          as="a"
          variant="link"
          class="text-blue-600 px-0 h-auto text-sm"
        >
          查看更多
        </Button>
      </CardHeader>
      <CardContent>
        <SmartTable
          :columns="userTableColumns"
          :rows="users"
          :pagination="false"
        />
      </CardContent>
    </Card>
  </div>
</template>
