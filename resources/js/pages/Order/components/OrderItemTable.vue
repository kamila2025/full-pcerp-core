<script setup>
import { ref, computed, watch } from 'vue'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
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
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { CirclePlus, CircleMinus, Pencil, Eye } from 'lucide-vue-next'
import OrderItemSelector from './item/OrderItemSelector.vue'

const props = defineProps({
  modelValue: Array,
  hidePrices: Boolean,
  hideCostPrice: Boolean,
  showSummaryOnly: Boolean,
  noTemplate: Boolean,
  templateItems: Array,
  shippingLocation: Object,
})

const emit = defineEmits(['update:modelValue'])

const isEditMode = ref(false)

const groupItems = ref([])

const tableHeadCount = computed(() => props.hidePrices ? 2 : 4)

const templateItems = props.templateItems || []

const orderItems = props.modelValue || []

groupItems.value = templateItems.map(item => ({
  category_id: item.category_id,
  name: item.attribute_name,
  template_item_id: item.id,
  items: orderItems.filter(i => i.template_item_id === item.id) || [],
  selected: null,
}))

// 其他
const elseItems = orderItems.filter(i => !i.template_item_id || i.template_item_id === null || !templateItems.map(m => m.id).includes(i.template_item_id)) || []

// 如果有其他項目，則加入其他類別
if (elseItems.length > 0 && !props.noTemplate) {
  groupItems.value.push({
    category_id: null,
    name: '其他',
    template_item_id: null,
    items: elseItems,
    selected: null,
  })
}

if (props.noTemplate) {
  groupItems.value.push({
    category_id: null,
    name: '訂單內容',
    template_item_id: null,
    items: orderItems,
    selected: null,
  })
}

// 從 URL hash 中取得要高亮的項目 ID
const highlightedItemId = computed(() => {
  const hash = window.location.hash
  const match = hash.match(/order-item-(\d+)/)
  return match ? parseInt(match[1]) : null
})

const addItem = group => {
  if (!group.selected) return

  group.items.push(Object.assign({}, group.selected))

  group.selected = null
}

const onItemSelection = (value, selected, templateId) => {
  if (!selected.variant_id) selected.variant_id = value.id

  if (!selected.quantity) selected.quantity = 1

  if (!selected.product_name) selected.product_name = value.product?.name

  if (!selected.variant_name) selected.variant_name = value.name

  selected.id = null

  selected.template_item_id = templateId

  selected.inventories = selected.inventories
}

watch(groupItems, () => {
  const items = groupItems.value.map(group => group.items).flat()

  const selected = groupItems.value.map(group => group.selected).filter(i => i)

  const allItems = [...items, ...selected]

  emit('update:modelValue', allItems)
}, { deep: true })
</script>

<template>
  <Table class="w-full table-fixed mb-12">
    <TableHeader>
      <TableRow>
        <TableHead class="w-2/4 whitespace-nowrap">
          產品項目
        </TableHead>
        <TableHead :class="['w-1/6 whitespace-nowrap text-center', { hidden: hidePrices }]">
          單價
        </TableHead>
        <TableHead class="w-1/6 whitespace-nowrap text-center">
          <div class="flex items-center justify-center">
            <span class="mr-1">數量</span>
            <component
              :is="isEditMode ? Eye : Pencil"
              class="w-4 h-4 cursor-pointer"
              @click="isEditMode = !isEditMode"
            />
          </div>
        </TableHead>
        <TableHead :class="['w-1/6 whitespace-nowrap text-center', { hidden: hidePrices }]">
          小計
        </TableHead>
      </TableRow>
    </TableHeader>

    <TableBody>
      <template
        v-for="(group, groupIndex) in groupItems"
        :key="`group-${groupIndex}`"
      >
        <!-- Group Name -->
        <TableRow v-if="!showSummaryOnly || showSummaryOnly && group.items.length > 0 || showSummaryOnly && group.selected">
          <TableCell
            :colspan="tableHeadCount"
            class="font-bold bg-gray-100 p-1"
          >
            {{ group.name }}
          </TableCell>
        </TableRow>

        <!-- Selected Item -->
        <TableRow v-show="showSummaryOnly === false && group.template_item_id || showSummaryOnly && group.selected || noTemplate">
          <TableCell class="p-1 w-[300px] max-w-[300px] ">
            <div class="flex items-center">
              <OrderItemSelector
                v-model="group.selected"
                group-by="product.brands.name_display"
                :category="group.category_id"
                :selected="modelValue.map(item => item.variant_id)"
                @update:modelValue="onItemSelection($event, group.selected, group.template_item_id)"
              />

              <Button
                :disabled="Boolean(group.selected) === false"
                variant="ghost"
                size="icon"
                class="flex items-center w-4 h-4 ml-1.5"
                @click="addItem(group)"
              >
                <CirclePlus class="w-4 h-4" />
              </Button>
            </div>
          </TableCell>
          <TableCell :class="['p-1 text-center ', { hidden: hidePrices }]">
            <div
              v-if="group.selected"
              class="flex flex-col"
            >
              <span>{{ group.selected?.price.toLocaleString() }} TWD</span>
              <span :class="['text-xs text-muted-foreground', { hidden: hideCostPrice }]">
                {{ group.selected.cost_price.toLocaleString() }} TWD
              </span>
            </div>
          </TableCell>
          <TableCell class="p-1 text-center place-items-center">
            <Input
              v-if="group.selected"
              v-model="group.selected.quantity"
              type="number"
              class="w-full text-center h-8"
              min="1"
            />
          </TableCell>
          <TableCell :class="['p-1 text-center ', { hidden: hidePrices }]">
            <div
              v-if="group.selected"
              class="flex flex-col"
            >
              <span>{{ (group.selected.price * group.selected.quantity).toLocaleString() }} TWD</span>
              <span :class="['text-xs text-muted-foreground', { hidden: hideCostPrice }]">
                {{ (group.selected.cost_price * group.selected.quantity).toLocaleString() }} TWD
              </span>
            </div>
          </TableCell>
        </TableRow>

        <!-- Items List -->
        <TableRow
          v-for="(item, itemIndex) in group.items"
          :key="`item-${itemIndex}`"
          :class="[
            'transition-colors duration-500',
            {
              'bg-yellow-50 border-l-4 border-yellow-500 shadow-sm': item.id && item.id === highlightedItemId,
              'hover:bg-gray-50': item.id !== highlightedItemId
            }
          ]"
        >
          <TableCell class="p-1">
            <div class="flex items-center space-x-1">
              <CircleMinus
                class="text-red-500 h-4 w-4 cursor-pointer flex-shrink-0"
                @click="group.items.splice(itemIndex, 1)"
              />
              <component
                :is="item.product_id ? 'a' : 'span'"
                :href="$can('products') ? `/products/${item?.product_id}/edit` : null"
                :target="'_blank'"
                :class="['break-all', { 'text-blue-600 w-auth': item.product_id, 'text-blue-600': item.product_id }]"
              >
                {{ item.product_name }}
                <small class="text-muted-foreground">{{ item.variant_name }}</small>
                <div
                  v-if="item.inventories.find(i => i.location_id === shippingLocation?.id)?.quantity < 0"
                  class="text-[80%] leading-none text-red-300"
                >
                  缺貨 {{ item.inventories.find(i => i.location_id === shippingLocation?.id)?.quantity }}
                </div>
              </component>
              <span
                v-if="item.id && item.id === highlightedItemId"
                class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-nowrap"
              >
                目前項目
              </span>
              <template v-if="!showSummaryOnly">
                <!-- <Badge
                  v-if="item.purchase_order_items?.filter(i => i.purchase_order.status === 'completed').reduce((sum, i) => sum + (i.quantity || 0), 0) < item.quantity && (item.variant_id === null || item.inventories.find(i => i.location_id === shippingLocation?.id)?.quantity < 0)"
                  variant="outline"
                  class="px-1 bg-rose-50 text-rose-700 text-nowrap"
                >
                  未到 {{ item.quantity - (item.purchase_order_items?.filter(i => i.purchase_order.status === 'completed').reduce((sum, i) => sum + (i.quantity || 0), 0) || 0) }}
                </Badge> -->
                <Badge
                  v-if="item?.received_quantity > 0"
                  variant="outline"
                  class="px-1 bg-green-50 text-green-700 text-nowrap "
                >
                  已到 {{ item.received_quantity }}
                </Badge>
                <Badge
                  v-if="item?.purchasing_quantity > 0"
                  variant="outline"
                  class="px-1 bg-yellow-50 text-yellow-700 text-nowrap "
                >
                  採購 {{ item.purchasing_quantity }}
                </Badge>
              </template>
            </div>
          </TableCell>
          <TableCell :class="['p-1 text-center', { hidden: hidePrices }]">
            <div class="flex flex-col">
              <span>{{ item.price.toLocaleString() }} TWD</span>
              <span :class="['text-xs text-muted-foreground', { hidden: hideCostPrice }]">
                {{ item.cost_price.toLocaleString() }} TWD
              </span>
            </div>
          </TableCell>
          <TableCell class="p-1 text-center">
            <Input
              v-if="isEditMode"
              v-model="item.quantity"
              type="number"
              class="text-center text-xs h-[22px]"
              min="1"
            />
            <template v-else>
              <span>{{ item.quantity }}</span>
              <Badge
                v-if="showSummaryOnly === false"
                variant="secondary"
                class="px-1 ml-1 text-nowrap"
              >
                {{ item.fulfillment_items?.reduce((sum, i) => sum + i.fulfilled_quantity, 0) || 0 }} 出貨
              </Badge>
            </template>
          </TableCell>
          <TableCell :class="['p-1 text-center', { hidden: hidePrices }]">
            <div class="flex flex-col">
              <span>{{ (item.price * item.quantity).toLocaleString() }} TWD</span>
              <span :class="['text-xs text-muted-foreground', { hidden: hideCostPrice }]">
                {{ (item.cost_price * item.quantity).toLocaleString() }} TWD
              </span>
            </div>
          </TableCell>
        </TableRow>
      </template>
    </TableBody>
  </Table>
</template>
