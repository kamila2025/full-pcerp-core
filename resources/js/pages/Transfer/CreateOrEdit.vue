<script setup>
import { ref, computed } from 'vue'
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
import InputError from '@/components/InputError.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  transferOrder: Object,
  users: Array,
  locations: Array,
})

const form = useForm({
  transfer_user_id: props.transferOrder?.transfer_user_id || null,
  receiver_user_id: props.transferOrder?.receiver_user_id || null,
  from_location_id: props.transferOrder?.from_location_id || null,
  to_location_id: props.transferOrder?.to_location_id || null,
  remark: props.transferOrder?.remark || null,
  items: props.transferOrder?.items || [],
})

const isEditMode = ref(props.transferOrder ? false : true)

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
const submit = () => {
  if (props.transferOrder) form.put(`/transfer-orders/${props.transferOrder.id}`)

  else form.post('/transfer-orders')
}

const onDeleting = () => {
  if (confirm('確定要刪除嗎？')) {
    router.delete(`/transfer-orders/${props.transferOrder.id}`)
  }
}

const changeStatus = status => {
  if (confirm('確定要完成調撥嗎？')) {
    router.put(`/transfer-orders/${props.transferOrder.id}/status`, {
      status,
    })
  }
}
</script>

<template>
  <AppHeader
    tag="portal"
    title="新增調撥單"
    is-back
    @back-action="router.get('/transfer-orders')"
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
        v-if="transferOrder"
        variant="destructive"
        size="icon"
        :disabled="form.processing"
        @click="onDeleting"
      >
        <Trash class="w-4 h-4" />
      </Button>
    </template>
  </AppHeader>

  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">基本資訊</h2>

      <Button
        v-if="transferOrder?.status === 'open'"
        class="bg-blue-500 hover:bg-blue-600"
        @click="changeStatus('transferring')"
      >
        開始調撥
      </Button>

      <Button
        v-if="transferOrder?.status === 'transferring'"
        class="bg-blue-500 hover:bg-blue-600"
        @click="changeStatus('completed')"
      >
        完成調撥
      </Button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="created_at">建立日期</Label>
        <div class="text-gray-900">{{ (transferOrder?.created_at) ? moment(transferOrder.created_at).format('YYYY-MM-DD HH:mm') : '-' }}</div>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="status">調撥狀態</Label>
        <Badge class="w-fit">
          {{ transferOrder?.status ? $t(`transfer.status.${transferOrder?.status}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="arrival_status">到貨狀態</Label>
        <Badge class="w-fit">
          {{ transferOrder?.arrival_status ? $t(`transfer.arrival_status.${transferOrder?.arrival_status}`) : '-' }}
        </Badge>
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="from_location_id">原始分店</Label>
        <Select v-model="form.from_location_id">
          <SelectTrigger id="from_location_id">
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
        <InputError :message="form.errors.from_location_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="transfer_user_id">調撥人員</Label>
        <Select v-model="form.transfer_user_id">
          <SelectTrigger id="transfer_user_id">
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
        <InputError :message="form.errors.transfer_user_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="to_location_id">目的分店</Label>
        <Select v-model="form.to_location_id">
          <SelectTrigger id="to_location_id">
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
        <InputError :message="form.errors.to_location_id" />
      </div>
      <div class="grid w-full max-w-sm items-center gap-1.5 self-baseline">
        <Label for="receiver_user_id">點收人員</Label>
        <Select v-model="form.receiver_user_id">
          <SelectTrigger id="receiver_user_id">
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
        <Label for="actual_time">送貨日期</Label>
        <Input
          v-model="form.scheduled_time"
          id="scheduled_time"
          type="date"
          placeholder="選擇日期"
        />
        <InputError :message="form.errors.scheduled_time" />
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

      <Button
        variant="link"
        class="text-blue-500"
        @click="isEditMode = !isEditMode"
      >
        {{ isEditMode ? '完成' : '編輯' }}
      </Button>
    </div>

    <!-- 商品搜尋和添加 -->
    <OrderItemSelector
      v-if="isEditMode"
      :selected="form.items.map(item => item.variant_id)"
      @select="onItemSelection"
    />

    <!-- 商品表格 -->
    <Table class="w-full table-fixed2 my-4">
      <TableHeader>
        <TableRow>
          <TableHead class="text-nowrap">商品</TableHead>
          <TableHead class="text-nowrap">款式</TableHead>
          <TableHead :class="isEditMode ? 'text-nowrap' : 'hidden'">原始分店</TableHead>
          <TableHead :class="isEditMode ? 'text-nowrap' : 'hidden'">目的分店</TableHead>
          <TableHead class="text-nowrap">調撥數量</TableHead>
          <TableHead :class="isEditMode ? 'w-0 text-right' : 'hidden'"></TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow
          v-for="(item, index) in form.items"
          :key="index"
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
          <TableCell :class="isEditMode ? '' : 'hidden'">{{ item.variant?.inventories.find(inventory => inventory.location_id === Number(form.from_location_id))?.quantity ?? '-' }}</TableCell>
          <TableCell :class="isEditMode ? '' : 'hidden'">{{ item.variant?.inventories.find(inventory => inventory.location_id === Number(form.to_location_id))?.quantity ?? '-' }}</TableCell>
          <TableCell>
            <Input
              v-if="isEditMode"
              type="number"
              v-model="item.quantity"
              class="min-w-16"
            />
            <div v-else>{{ item.quantity }}</div>
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