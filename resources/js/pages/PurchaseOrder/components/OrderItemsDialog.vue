<script setup>
import { ref, computed, watch } from 'vue'
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-vue-next'

const props = defineProps({
  ordersItems: {
    type: Array,
    default: () => [],
  },
  selectedItems: {
    type: Array,
    default: () => [],
  },
  locationId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits([
  'select',
])

const open = ref(false)

const search = ref('')

const selected = ref([])

const handleSelect = item => {
  if (props.selectedItems.some(i => i.order_item_id === item.id)) return

  const index = selected.value.findIndex(i => i.id === item.id)

  if (index === -1) selected.value.push(item)

  else selected.value.splice(index, 1)
}

const handleSelectAll = checked => {
  if (checked) {
    selected.value = [...props.ordersItems]
  } else {
    selected.value = []
  }
}

const isAllSelected = computed(() => {
  return props.ordersItems.length > 0 && selected.value.length === props.ordersItems.length
})

const filteredItems = computed(() => props.ordersItems
  .filter(item =>
    item.inventories.find(i => i.location_id === props.locationId)?.quantity < 0
    || (item.variant_id === null && item.order.location_id === props.locationId)
  )
  .filter(item =>
    item.product_name.includes(search.value) ||
    item.order.order_number.includes(search.value) ||
    item.order.customer?.name.includes(search.value)
  ))

const handleConfirm = () => {
  emit('select', selected.value)

  open.value = false

  selected.value = []
}

watch(open, () => {
  selected.value = []
})
</script>

<template>
  <Dialog
    :open="open"
    @update:open="open = $event"
  >
    <DialogTrigger asChild>
      <Button variant="outline">
        選擇未進貨明細
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-[800px]">
      <DialogHeader>
        <DialogTitle>選擇未進貨明細</DialogTitle>
        <DialogDescription>
          選擇要加入的未進貨明細項目
        </DialogDescription>
      </DialogHeader>

      <div class="relative w-full items-center">
        <Input
          v-model="search"
          type="text"
          placeholder="Search..."
          class="pl-10"
        />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
          <Search class="size-6 text-muted-foreground" />
        </span>
      </div>

      <div class="max-h-[400px] overflow-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12">
                <Checkbox
                  :model-value="isAllSelected"
                  @update:model-value="handleSelectAll"
                />
              </TableHead>
              <TableHead>商品</TableHead>
              <TableHead class="text-nowrap">數量</TableHead>
              <TableHead class="text-nowrap">未進數</TableHead>
              <TableHead class="text-nowrap">訂單</TableHead>
              <TableHead class="text-nowrap">客戶</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in filteredItems"
              :key="item.id"
              :class="{
                'bg-muted': selected.some(i => i.id === item.id),
                'opacity-50': selectedItems.some(i => i.order_item_id === item.id),
              }"
              class="cursor-pointer hover:bg-muted/50"
              @click="handleSelect(item)"
            >
              <TableCell>
                <Checkbox
                  :model-value="selected.some(i => i.id === item.id)"
                  @update:checked="handleSelect(item)"
                />
              </TableCell>
              <TableCell>
                <span>{{ item.product_name }}</span>
                <small class="text-muted-foreground ml-1">{{ item.variant_name }}</small>
              </TableCell>
              <TableCell>{{ item.quantity }}</TableCell>
              <TableCell>{{ item.quantity - (item.purchase_order_items_sum_quantity || 0) }}</TableCell>
              <TableCell>
                <a
                  :href="`/orders/${item.order.id}/edit#order-item-${item.id}`"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-400 no-underline"
                >
                  {{ item.order.order_number }}
                </a>
              </TableCell>
              <TableCell>
                <component
                  target="_blank"
                  :is="item.order.customer ? 'a' : 'span'"
                  :href="`/customers/${item.order.customer_id}/edit#order-item-${item.id}`"
                  :class="{ 'text-blue-600 hover:text-blue-400 no-underline': item.order.customer }"
                >
                  {{ item.order.customer?.name }}
                </component>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <DialogFooter>
        <Button @click="handleConfirm">
          確認
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
