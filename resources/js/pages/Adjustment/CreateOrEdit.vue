<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
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
import { Trash, X } from 'lucide-vue-next'
import OrderItemSelector from '@/pages/Order/components/item/OrderItemSelector.vue'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import InputError from '@/components/InputError.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  adjustmentOrder: Object,
  users: Array,
  locations: Array,
  // 
  params: Object,
})

const form = useForm({
  user_id: props.adjustmentOrder?.user_id || null,
  location_id: props.adjustmentOrder?.location_id || null,
  items: props.adjustmentOrder?.items || [],
})

const highlightedItemId = computed(() => {
  const hash = window.location.hash
  const match = hash.match(/order-item-(\d+)/)
  return match ? parseInt(match[1]) : null
})

const isEditMode = computed(() => !props.adjustmentOrder || props.adjustmentOrder?.status === 'open' || props.adjustmentOrder?.status === 'in_progress')

const onItemSelection = item => {
  form.items.push({
    product: item.product,
    variant: Object.assign({}, item, { inventories: item.inventories || [] }),
    variant_id: item?.id,
    product_name: item.product_name || item.product?.name,
    variant_name: item.variant_name || item.variant?.name,
    quantity: item.quantity || 0,
  })
}

// 提交表單
const submit = options => {
  if (props.adjustmentOrder) form.put(`/adjustment-orders/${props.adjustmentOrder.id}`, options)

  else form.post('/adjustment-orders', options)
}

const onDeleting = () => {
  if (confirm('確定要刪除盤點單嗎？')) {
    router.delete(`/adjustment-orders/${props.adjustmentOrder.id}`)
  }
}

const changeStatus = status => {
  if (confirm('確定要完成盤點嗎？')) {
    submit({
      onSuccess: () => {
        router.put(`/adjustment-orders/${props.adjustmentOrder.id}/status`, {
          status,
        }, {
          onSuccess: page => {
            const { props } = page

            form.items = props.adjustmentOrder.items
          }
        })
      }
    })
  }
}

const corrected = () => {
  if (confirm('確定要校正庫存嗎？')) {
    router.put(`/adjustment-orders/${props.adjustmentOrder.id}/corrected`)
  }
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="adjustmentOrder ? adjustmentOrder.order_number : `新增盤點單`"
    is-back
    @back-action="router.get(params.back_url || '/adjustment-orders')"
  >
    <template #actions>
      <Button
        variant="secondary"
        :disabled="form.processing"
        @click="submit"
      >
        Save
      </Button>

      <Button
        v-if="adjustmentOrder"
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

      <div>
        <Button
          v-if="adjustmentOrder?.status === 'open' || adjustmentOrder?.status === 'in_progress'"
          class="bg-blue-500 hover:bg-blue-600"
          @click="changeStatus('completed')"
        >
          完成盤點
        </Button>

        <Button
          v-if="adjustmentOrder?.result === 'unmatch'"
          class="bg-blue-500 hover:bg-blue-600"
          @click="corrected()"
        >
          庫存校正
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="created_at">建立日期</Label>
        <div class="text-gray-900">{{ (adjustmentOrder?.created_at) ? moment(adjustmentOrder.created_at).format('YYYY-MM-DD HH:mm') : '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="status">盤點狀態</Label>
        <Badge class="w-fit">
          {{ adjustmentOrder?.status ? $t(`adjustment.status.${adjustmentOrder?.status}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="result">盤點結果</Label>
        <Badge class="w-fit">
          {{ adjustmentOrder?.result ? $t(`adjustment.result.${adjustmentOrder?.result}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="location_id">分店</Label>
        <Select
          v-model="form.location_id"
          :disabled="!isEditMode"
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
        <Label for="user_id">盤點人員</Label>
        <Select
          v-model="form.user_id"
          :disabled="!isEditMode"
        >
          <SelectTrigger id="user_id">
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
        <InputError :message="form.errors.user_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="start_at">盤點開始時間</Label>
        <div class="text-gray-900">{{ (adjustmentOrder?.start_at) ? moment(adjustmentOrder.start_at).format('YYYY-MM-DD HH:mm') : '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="end_at">盤點結束時間</Label>
        <div class="text-gray-900">{{ (adjustmentOrder?.end_at) ? moment(adjustmentOrder.end_at).format('YYYY-MM-DD HH:mm') : '-' }}</div>
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

  <!-- 商品項目 -->
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-xl font-bold mb-4">盤點商品</h2>

    <!-- 商品搜尋和添加 -->
    <OrderItemSelector
      v-if="isEditMode"
      :selected="form.items.map(item => item.variant_id)"
      @select="onItemSelection"
    />

    <!-- 商品表格 -->
    <Table class="w-full my-4">
      <TableHeader>
        <TableRow>
          <TableHead class="text-nowrap">商品</TableHead>
          <TableHead class="text-nowrap">款式</TableHead>
          <TableHead class="text-nowrap">系統庫存</TableHead>
          <TableHead class="text-nowrap">盤點數量</TableHead>
          <TableHead class="text-nowrap">差異數量</TableHead>
          <TableHead :class="isEditMode ? 'w-0 text-right' : 'hidden'"></TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow
          v-for="(item, index) in form.items"
          :key="index"
          :class="[
            'transition-colors duration-500',
            { 
              'bg-yellow-50 border-l-4 border-yellow-500 shadow-sm': item.id === highlightedItemId,
              'hover:bg-gray-50': item.id !== highlightedItemId
            }
          ]"
        >
          <TableCell>
            <TextLink
              :href="`/products/${item.variant?.product_id}/edit`"
              class="text-blue-600 hover:text-blue-400 no-underline"
            >
              {{ item.product_name || item.product.name }}
            </TextLink>
          </TableCell>
          <TableCell></TableCell>
          <TableCell>{{ item.original_quantity ?? '-' }}</TableCell>
          <TableCell>
            <Input
              v-if="isEditMode"
              type="number"
              v-model="item.quantity"
            />
            <span v-else>{{ item.quantity ?? '-' }}</span>
          </TableCell>
          <TableCell :class="{
              'text-red-600': item.difference_quantity < 0,
              'text-green-600': item.difference_quantity > 0
            }">
            {{ item.difference_quantity ?? '-' }}
          </TableCell>
          <TableCell :class="isEditMode ? 'w-0 text-right' : 'hidden'">
            <Button
              variant="ghost"
              size="icon"
              class="text-gray-400 hover:text-red-600"
              @click="form.items.splice(index, 1)"
            >
              <X class="w-4 h-4" />
            </Button>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>