<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Trash, X, Printer, ChevronDown, Group, List, Download } from 'lucide-vue-next'
import OrderItemSelector from '@/pages/Order/components/item/OrderItemSelector.vue'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import InputError from '@/components/InputError.vue'
import moment from 'moment'
import OrderItemsDialog from './components/OrderItemsDialog.vue'

const { t } = useI18n()

// ------------------------------------------------
const props = defineProps({
  purchaseOrder: Object,
  users: Array,
  locations: Array,
  //
  params: Object,
  ordersItems: Array,
})

const form = useForm({
  location_id: props.purchaseOrder?.location_id || null,
  issuer_user_id: props.purchaseOrder?.issuer_user_id || null,
  executor_user_id: props.purchaseOrder?.executor_user_id || null,
  custom_number: props.purchaseOrder?.custom_number || null,
  other_fee: props.purchaseOrder?.other_fee || 0,
  total_amount: props.purchaseOrder?.total_amount || 0,
  remark: props.purchaseOrder?.remark || null,
  scheduled_time: props.purchaseOrder?.scheduled_time || null,
  arrival_status: props.purchaseOrder?.arrival_status || null,
  items: props.purchaseOrder?.items || [],
})

const isEditMode = ref(props.purchaseOrder ? false : true)
const groupSameItems = ref(false) // 控制是否合併相同商品

// 選擇商品
const onItemSelection = item => {
  form.items.push({
    product: item.product,
    variant: Object.assign({}, item, { inventories: item.inventories || [] }),
    product_id: item.product_id,
    product_name: item.product?.name ?? item.product_name,
    variant_id: item.id,
    variant_name: item.name,
    quantity: item.quantity || 0,
    price: item.price || 0,
    cost_price: item.cost_price || 0,
  })
}

// 選擇未進貨明細
const onUndeliveredItemSelect = (items) => {
  // 添加新選中的項目
  items.forEach(item => {
    form.items.push({
      product: item.product,
      variant: Object.assign({}, item.variant, { inventories: item.variant?.inventories || [] }),
      order_item_id: item.id,
      order: item.order,
      product_id: item.product_id,
      product_name: item.product_name || item.product?.name,
      variant_id: item.variant_id,
      variant_name: item.variant_name,
      quantity: item.quantity - (item.purchase_order_items_sum_quantity || 0),
      price: item.price || 0,
      cost_price: item.cost_price || 0,
    })
  })
}

// 計算總數量
const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0)
})

// 計算總金額
const totalCost = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + ((Number(item.quantity) || 0) * (Number(item.cost_price) || 0))
  }, 0)
})

// 處理顯示的商品列表（合併或不合併）
const displayItems = computed(() => {
  if (!groupSameItems.value) {
    return form.items
  }

  // 合併相同商品
  const groupedItems = new Map()

  form.items.forEach(item => {
    const isFromOrder = Boolean(item.order_item_id)
    const key = `${item.product_id}-${item.variant_id}`

    if (groupedItems.has(key)) {
      const existing = groupedItems.get(key)
      const totalQuantity = Number(existing.quantity) + Number(item.quantity)
      const totalCost = (Number(existing.quantity) * Number(existing.cost_price)) + (Number(item.quantity) * Number(item.cost_price))
      const avgCostPrice = totalQuantity > 0 ? totalCost / totalQuantity : 0

      groupedItems.set(key, {
        ...existing,
        quantity: totalQuantity,
        cost_price: avgCostPrice,
        groupedCount: (existing.groupedCount || 1) + 1,
        orderItemsCount: (existing.orderItemsCount || 0) + (isFromOrder ? 1 : 0),
        directItemsCount: (existing.directItemsCount || 0) + (isFromOrder ? 0 : 1),
        originalItems: [...(existing.originalItems || [existing]), item],
        hasOrderItems: existing.hasOrderItems || isFromOrder,
        hasDirectItems: existing.hasDirectItems || !isFromOrder
      })
    } else {
      groupedItems.set(key, {
        ...item,
        groupedCount: 1,
        orderItemsCount: isFromOrder ? 1 : 0,
        directItemsCount: isFromOrder ? 0 : 1,
        originalItems: [item],
        hasOrderItems: isFromOrder,
        hasDirectItems: !isFromOrder
      })
    }
  })

  // 排序：按商品名稱，再按款式名稱
  return Array.from(groupedItems.values()).sort((a, b) => {
    const productComparison = (a.product_name || '').localeCompare(b.product_name || '')
    if (productComparison !== 0) return productComparison

    return (a.variant_name || '').localeCompare(b.variant_name || '')
  })
})

// 提交表單
const submit = () => {
  if (props.purchaseOrder) form.put(`/purchase-orders/${props.purchaseOrder.id}`)

  else form
    .transform(data => ({ ...data, type: props.params.type }))
    .post('/purchase-orders', {
      onSuccess: response => {
        form.defaults(response.props.purchaseOrder)

        form.reset();
      },
    })
}
// inertia
const onDeleting = () => {
  if (confirm('確定要刪除嗎？')) {
    router.delete(`/purchase-orders/${props.purchaseOrder.id}`)
  }
}

const changeStatus = status => {
  if (confirm(`確定要${status === 'completed' ? '完成' : '取消'}${t(`purchase.type.${props.purchaseOrder?.type}`)}嗎？`)) {
    router.put(`/purchase-orders/${props.purchaseOrder.id}/status`, {
      status,
    })
  }
}

// 列印功能
const printPurchaseOrder = () => {
  const url = new URL(`/purchase-orders/${props.purchaseOrder.id}/print`, window.location.origin)
  url.searchParams.append('group_same_items', groupSameItems.value ? '1' : '0')

  window.open(url.toString(), '_blank')
}

const printPurchaseOrderWithoutAmounts = () => {
  const url = new URL(`/purchase-orders/${props.purchaseOrder.id}/print-without-amounts`, window.location.origin)
  url.searchParams.append('group_same_items', groupSameItems.value ? '1' : '0')

  window.open(url.toString(), '_blank')
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="purchaseOrder ? purchaseOrder.order_number : `新增${$t(`purchase.type.${params.type}`)}單`"
    is-back
    @back-action="router.get('/purchase-orders')"
  >
    <template #actions>
      <DropdownMenu v-if="purchaseOrder">
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            :disabled="form.processing"
            v-tooltip="{ content: '列印選項', triggers: ['hover'] }"
            class="px-3"
          >
            <Printer class="w-4 h-4" />
            <ChevronDown class="w-3 h-3 ml-1" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem @click="printPurchaseOrder">
            <Printer class="mr-2 h-4 w-4" />
            列印（含金額）{{ groupSameItems ? ' - 合併模式' : ' - 詳細模式' }}
          </DropdownMenuItem>
          <DropdownMenuItem @click="printPurchaseOrderWithoutAmounts">
            <Printer class="mr-2 h-4 w-4" />
            列印（無金額）{{ groupSameItems ? ' - 合併模式' : ' - 詳細模式' }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <Button
        variant="secondary"
        :disabled="form.processing"
        @click="submit"
      >
        Save
      </Button>

      <Button
        v-if="purchaseOrder"
        variant="destructive"
        size="icon"
        :disabled="form.processing"
        @click="onDeleting"
      >
        <Trash class="w-4 h-4" />
      </Button>
    </template>
  </AppHeader>

  <!-- 基本資訊 -->
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">基本資訊</h2>

      <Button
        v-if="purchaseOrder?.type === 'purchase' && (purchaseOrder?.status === 'open' || purchaseOrder?.status === 'pending')"
        class="bg-blue-500 hover:bg-blue-600"
        @click="changeStatus('completed')"
      >
        完成進貨
      </Button>

      <Button
        v-if="purchaseOrder?.type === 'return' && (purchaseOrder?.status === 'open' || purchaseOrder?.status === 'pending')"
        class="bg-blue-500 hover:bg-blue-600"
        @click="changeStatus('completed')"
      >
        完成退貨
      </Button>

      <Button
        v-if="purchaseOrder?.type === 'purchase' && purchaseOrder?.status === 'completed'"
        variant="destructive"
        @click="changeStatus('cancelled')"
      >
        取消進貨
      </Button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="created_at">建立日期</Label>
        <div class="text-gray-900">{{ moment(purchaseOrder?.created_at).format('YYYY-MM-DD HH:mm') || '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="type">種類</Label>
        <div class="text-gray-900">{{ $t(`purchase.type.${params.type || purchaseOrder?.type}`) }}</div>
        <InputError :message="form.errors.type" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="status">貨單狀態</Label>
        <Badge class="w-fit">
          {{ purchaseOrder?.status ? $t(`purchase.status.${purchaseOrder?.status}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="arrival_status">到貨狀態</Label>
        <Badge class="w-fit">
          {{ purchaseOrder?.arrival_status ? $t(`purchase.arrival_status.${purchaseOrder?.arrival_status}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="location_id">分店<span class="text-red-500 ml-1">*</span></Label>
        <Select
          v-model="form.location_id"
          :disabled="purchaseOrder?.status === 'completed'"
        >
          <SelectTrigger id="location_id">
            <SelectValue placeholder="選擇分店" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="location in locations"
              :key="location.id"
              :value="location.id"
            >
              {{ location.name }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.location_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="issuer_user_id">進貨人員<span class="text-red-500 ml-1">*</span></Label>
        <Select v-model="form.issuer_user_id">
          <SelectTrigger id="issuer_user_id">
            <SelectValue placeholder="選擇人員" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="user in users"
              :key="user.id"
              :value="user.id"
            >
              {{ user.name }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.issuer_user_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="executor_user_id">入庫人員</Label>
        <Select v-model="form.executor_user_id">
          <SelectTrigger id="executor_user_id">
            <SelectValue placeholder="選擇人員" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="user in users"
              :key="user.id"
              :value="user.id"
            >
              {{ user.name }}
            </SelectItem>
          </SelectContent>
        </Select>
        <InputError :message="form.errors.executor_user_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="expected_cost">預期成本 / 實際成本</Label>
        <div class="text-gray-900">{{ purchaseOrder?.total_amount.toLocaleString() || '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="other_fee">其他費用</Label>
        <div class="relative w-full max-w-sm items-center">
          <Input
            v-model="form.other_fee"
            type="number"
            id="other_fee"
            class="pl-10 text-right"
          />
          <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2 text-gray-500">
            NT$
          </span>
        </div>
        <InputError :message="form.errors.other_fee" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="scheduled_time">預定到貨日期</Label>
        <Input
          v-model="form.scheduled_time"
          id="scheduled_time"
          type="date"
          placeholder="選擇日期"
        />
        <InputError :message="form.errors.scheduled_time" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="actual_time">完成進貨日期</Label>
        <div class="text-gray-900">{{ purchaseOrder?.actual_time || '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="custom_number">自訂單號</Label>
        <Input
          v-model="form.custom_number"
          id="custom_number"
          placeholder="自訂單號"
        />
        <InputError :message="form.errors.custom_number" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="remark">備註</Label>
        <Input
          v-model="form.remark"
          id="remark"
          placeholder="備註"
        />
        <InputError :message="form.errors.remark" />
      </div>
    </div>
  </div>

  <!-- 進貨商品 -->
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-xl font-bold">進貨商品</h2>

      <div class="flex items-center space-x-2">
        <Button
          variant="outline"
          size="icon"
          @click="groupSameItems = !groupSameItems"
          :class="groupSameItems ? 'bg-blue-50 text-blue-600 border-blue-300' : ''"
          v-tooltip="{ 
            content: groupSameItems ? '顯示詳細列表' : '合併相同商品', 
            triggers: ['hover'] 
          }"
        >
          <Group
            v-if="groupSameItems"
            class="w-4 h-4"
          />
          <List
            v-else
            class="w-4 h-4"
          />
        </Button>

        <Button
          variant="link"
          class="text-blue-500"
          @click="isEditMode = !isEditMode"
        >
          {{ isEditMode ? '完成' : '編輯' }}
        </Button>
      </div>
    </div>

    <div class="space-y-2">
      <!-- 商品搜尋和添加 -->
      <OrderItemSelector
        v-if="isEditMode"
        :selected="form.items.filter(item => !item.order_item_id).map(item => item.variant_id)"
        @select="onItemSelection"
      />

      <!-- 選擇未進貨明細 -->
      <OrderItemsDialog
        :orders-items="ordersItems"
        :location-id="form.location_id"
        @select="onUndeliveredItemSelect"
      />
    </div>

    <Table class="w-full2 my-4">
      <TableHeader>
        <TableRow>
          <TableHead class="text-nowrap">商品</TableHead>
          <TableHead class="text-nowrap">款式</TableHead>
          <TableHead :class="isEditMode ? 'text-nowrap' : 'hidden'">庫存</TableHead>
          <TableHead class="text-nowrap">數量</TableHead>
          <TableHead class="text-nowrap">價格</TableHead>
          <TableHead class="text-nowrap">成本</TableHead>
          <TableHead class="text-nowrap">小計</TableHead>
          <TableHead :class="isEditMode ? 'w-0 text-right' : 'hidden'"></TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow
          v-for="(item, index) in displayItems"
          :key="groupSameItems ? `${item.product_id}-${item.variant_id}` : index"
          :class="['transition-colors duration-500', {'bg-red-50 !border-l-4 border-red-500': Boolean(form.errors[`items.${index}`]) }]"
        >
          <TableCell>
            <div class="flex items-center gap-2">
              <component
                :is="item.product_id ? 'a' : 'span'"
                :href="item.product_id ? `/products/${item?.product_id}/edit` : null"
                target="_blank"
                :class="{'text-blue-600 hover:text-blue-400': item.product_id}"
              >
                {{ item.product_name }}
              </component>
              <span
                v-if="groupSameItems && item.groupedCount > 1"
                class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-nowrap"
                v-tooltip="{ 
                  content: `合併了 ${item.groupedCount} 行商品${item.hasOrderItems && item.hasDirectItems ? '（包含拋轉單和直接添加）' : item.hasOrderItems ? '（全部來自拋轉單）' : '（全部直接添加）'}`, 
                  triggers: ['hover'] 
                }"
              >
                合併 {{ item.groupedCount }}
              </span>
              <span
                v-if="groupSameItems && item.hasOrderItems"
                class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-nowrap"
                v-tooltip="{ 
                  content: `其中 ${item.orderItemsCount} 筆來自拋轉單`, 
                  triggers: ['hover'] 
                }"
              >
                拋轉 {{ item.orderItemsCount }}
              </span>
              <a
                v-if="item.order && !groupSameItems"
                :href="`/orders/${item.order.id}/edit#order-item-${item.order_item_id}`"
                target="_blank"
                v-tooltip="{ content: `拋轉單：${item.order.order_number}`, triggers: ['click', 'hover', 'focus'] }"
                class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-nowrap"
              >
                拋轉單
              </a>
            </div>
          </TableCell>
          <TableCell>{{ item.variant_name }}</TableCell>
          <TableCell :class="isEditMode ? '' : 'hidden'">
            {{ item.variant?.inventories?.find(inventory => inventory.location_id === Number(form.location_id))?.quantity ?? '-' }}
          </TableCell>
          <TableCell>
            <Input
              v-if="isEditMode && !groupSameItems"
              type="number"
              v-model="item.quantity"
              :disabled="purchaseOrder?.status === 'completed'"
              :class="['min-w-16 max-w-32', {'border-red-500': Boolean(form.errors[`items.${index}.quantity`])}] "
            />
            <div v-else>{{ item.quantity }}</div>
            <InputError :message="form.errors[`items.${index}.quantity`]" />
          </TableCell>
          <TableCell>NT$ {{ item.price.toLocaleString() }}</TableCell>
          <TableCell>
            <Input
              v-if="isEditMode && !groupSameItems"
              type="number"
              v-model="item.cost_price"
              v-tooltip="{ content: `${item.cost_price || 0}`, triggers: ['click', 'focus', 'blur'] }"
              :class="['min-w-16 max-w-32', {'border-red-500': Boolean(form.errors[`items.${index}.cost_price`])}]"
            />
            <div v-else>
              NT$ {{ Number(item.cost_price).toLocaleString() || 0 }}
              <span
                v-if="groupSameItems && item.groupedCount > 1"
                class="text-xs text-gray-500 ml-1"
              >
                (平均)
              </span>
            </div>
            <InputError :message="form.errors[`items.${index}.cost_price`]" />
          </TableCell>
          <TableCell>
            NT$ {{ ((item.cost_price || 0) * (item.quantity || 0)).toLocaleString() }}
          </TableCell>
          <TableCell :class="isEditMode && !groupSameItems ? 'w-0 text-right' : 'hidden'">
            <Button
              v-if="!groupSameItems"
              :disabled="purchaseOrder?.status === 'completed'"
              variant="ghost"
              size="icon"
              class="text-gray-400 hover:text-red-600"
              @click="form.items.splice(index, 1)"
            >
              <X class="w-4 h-4" />
            </Button>
          </TableCell>
        </TableRow>

        <!-- 如果沒有商品，顯示錯誤訊息 -->
        <TableRow v-if="form.errors.items">
          <TableCell
            :colspan="isEditMode && !groupSameItems ? 8 : 7"
            class="text-center text-red-500 text-sm"
          >
            {{ form.errors.items }}
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <!-- 總計 -->
    <div class="flex justify-between items-center text-sm">
      <div>總數量 <span class="font-medium">{{ totalQuantity }}</span></div>
      <div class="text-red-600">總成本 NT$ <span class="font-medium">{{ totalCost.toLocaleString() }}</span></div>
    </div>
  </div>
</template>
