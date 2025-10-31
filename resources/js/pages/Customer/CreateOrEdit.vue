@@ -0,0 +1,81 @@
<script setup>
import { useForm, router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/components/ui/select'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { SmartTable } from '@/components/app/table'
import { LocationDialog } from '@/pages/Order/components/dialog'
import { Receipt, ShoppingCart } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import InputError from '@/components/InputError.vue'
import ActivityLog from '@/components/ActivityLog.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  customer: Object,
  activities: Array,
  orders: Array,
})

// ------------------------------------------------
const form = useForm({
  name: props.customer?.name || null,
  email: props.customer?.email || null,
  phone: props.customer?.phone || null,
  additional: props.customer?.additional || {
    id_number: null,
    carrier: null,
    customer_source: null,
  },
})

// 提交表單
const submit = () => {
  if (props.customer) form.put(`/customers/${props.customer.id}`)

  else form.post('/customers')
}

const getStatusClass = (status) => {
  return {
    'bg-yellow-100 text-yellow-800': status === 'unpaid' || status === 'unfulfilled',
    'bg-green-100 text-green-800': status === 'paid' || status === 'fulfilled'
  }
}

// Column
const orderTableColumns = [
  {
    accessorKey: 'order_number',
    header: '訂單編號',
    class: '',
  },
  {
    accessorKey: 'created_at',
    header: '訂單日期',
    class: '',
  },
  {
    accessorKey: 'financial_status',
    header: '付款狀態',
    class: '',
  },
  {
    accessorKey: 'fulfillment_status',
    header: '出貨狀態',
    class: '',
  },
  {
    accessorKey: 'amount',
    header: '總金額',
    class: 'text-right',
  },
]

const mapStatusVariant = (status) => {
  switch (status) {
    case 'paid':
    case 'fulfilled':
    case 'pickup':
      return 'secondary'
    case 'pickup':
      return 'outline'
    default:
      return 'default'
  }
}
</script>


<template>
  <AppHeader
    tag="portal"
    :title="customer ? customer.name : '新增客戶'"
    is-back
    @backAction="router.get('/customers')"
  >
    <template #actions>
      <Button
        variant="secondary"
        @click="submit"
      >
        Save
      </Button>
    </template>
  </AppHeader>

  <form
    @submit.prevent="submit"
    :class="['grid gap-2 lg:gap-4', customer ? 'grid-cols-3' : 'grid-cols-1']"
  >
    <div class="col-span-3 lg:col-span-2 space-y-2">
      <Card class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="col-1">
          <Label for="name">客戶名字*</Label>
          <Input
            id="name"
            v-model="form.name"
          />
          <InputError :message="form.errors.name" />
        </div>

        <div class="col-1">
          <Label for="customer_source">客戶來源</Label>
          <Select v-model="form.additional.customer_source">
            <SelectTrigger id="gateway_id">
              <SelectValue placeholder="選擇來源" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="line">LINE</SelectItem>
              <SelectItem value="facebook">Facebook</SelectItem>
              <SelectItem value="instagram">Instagram</SelectItem>
              <SelectItem value="referral">轉介紹</SelectItem>
              <SelectItem value="relation">緣故</SelectItem>
              <SelectItem value="store">店面客</SelectItem>
              <SelectItem value="zingala">銀角商城</SelectItem>
              <SelectItem value="website">官網</SelectItem>
              <SelectItem value="outbound">陌生開發</SelectItem>
              <SelectItem value="other">其他</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="col-1">
          <Label for="email">電子郵件</Label>
          <Input
            id="email"
            type="email"
            v-model="form.email"
          />
        </div>

        <div class="col-1">
          <Label for="phone">電話號碼</Label>
          <Input
            id="phone"
            type="tel"
            v-model="form.phone"
          />
        </div>

        <div class="col-1">
          <Label for="additional_id_number">身份證字號</Label>
          <Input
            id="additional_id_number"
            v-model="form.additional.id_number"
          />
        </div>

        <div class="col-1">
          <Label for="additional_carrier">門號電信</Label>
          <Select v-model="form.additional.carrier">
            <SelectTrigger id="gateway_id">
              <SelectValue placeholder="選擇電信" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="cht">中華電信</SelectItem>
              <SelectItem value="fareastone">遠傳電信</SelectItem>
              <SelectItem value="taiwan_mobile">台灣大哥大</SelectItem>
              <SelectItem value="gt">亞太電信</SelectItem>
              <SelectItem value="other">其他</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </Card>

      <!-- 訂單記錄 -->
      <Card
        v-if="customer && orders?.length"
        class="mt-6"
      >
        <CardHeader class="pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-1">
              <span>下單紀錄</span>
            </CardTitle>
            <div class="flex items-center gap-2">
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="router.get('/orders', { search: 'customer_id:' + customer.id })"
              >
                查看所有
              </Button>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                @click="router.get('/orders/create', { customer_id: customer.id })"
              >
                新增訂單
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <SmartTable
            :columns="orderTableColumns"
            :rows="orders"
            :pagination="false"
            thead-class="hidden"
          >
            <template #cell(order_number)="data">
              <TextLink
                :href="`/orders/${data.row.id}/edit`"
                class="text-blue-600 hover:text-blue-400 no-underline"
              >
                {{ data.value }}
              </TextLink>
              <span class="text-xs text-yellow-600">{{ data.row.attribution_user?.name }}</span>
            </template>

            <template #cell(created_at)="data">
              <span class="whitespace-nowrap">{{ moment(data.value).fromNow() }}</span>
            </template>

            <template #cell(financial_status)="data">
              <TextLink :href="`/orders/${data.row.id}/edit`">
                <Badge
                  :variant="mapStatusVariant(data.value)"
                  class="whitespace-nowrap"
                >
                  {{ $t('order.payment.' + data.value) }}
                </Badge>
              </TextLink>
            </template>

            <template #cell(fulfillment_status)="data">
              <TextLink :href="`/orders/${data.row.id}/edit`">
                <Badge
                  :variant="mapStatusVariant(data.value)"
                  class="whitespace-nowrap"
                >
                  {{ $t('order.fulfillment.' + data.value) }}
                </Badge>
              </TextLink>
            </template>

            <template #cell(amount)="data">
              {{ data.value.toLocaleString() }}
              <span class="text-gray-500">TWD</span>
            </template>
          </SmartTable>
        </CardContent>
      </Card>

      <ActivityLog
        v-if="customer"
        :activities="activities"
        class="mt-6"
      />
    </div>

    <div
      v-if="customer"
      class="col-span-3 lg:col-span-1 space-y-2"
    >
      <!-- 門市銷售認列 -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center gap-1">
            <Receipt class="w-4 h-4 text-muted-foreground" />
            <span>門市銷售認列</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <UserDialog>
            <Button
              variant="link"
              disabled
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              銷售員
              <br>
              {{ customer.attribution_user?.name || '-' }}
            </Button>
          </UserDialog>

          <hr>

          <LocationDialog>
            <Button
              variant="link"
              disabled
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              地點
              <br>
              {{ customer.attribution_location?.name || '-' }}
            </Button>
          </LocationDialog>
        </CardContent>
      </Card>
    </div>
  </form>
</template>
