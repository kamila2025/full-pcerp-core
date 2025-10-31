<script setup>
import { ref } from 'vue'
import { useForm, router, WhenVisible } from '@inertiajs/vue3'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/components/ui/select'
import {
  Table,
  TableHeader,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Loader2, Tag } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  order: { type: null },
})

const isOpen = ref(false)

const options = ref([])

const form = useForm({
  purchase_order_id: null,
  remark: null,
  items: props.order.items.map(item => ({
    order_id: item.id,
    product_name: item.product_name,
    quantity: 0,
    max_quantity: item.quantity - item.purchase_order_items.filter(i => i.purchase_order.status !== 'cancelled').reduce((sum, i) => sum + i.quantity, 0),
  })),
})

const onPurchase = () => {
  form
    .transform(data => ({ ...data, items: data.items.filter(item => item.quantity > 0) }))
    .post(`/orders/${props.order.id}/purchase`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        window.location.reload()
      },
    })
}

const fetchData = open => {
  router.reload({
    only: ['purchaseOrders'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.purchaseOrders || []
    },
  })
}
</script>

<template>
  <Dialog
    v-model:open="isOpen"
    @update:open="fetchData"
  >
    <DialogTrigger as-child>
      <Button
        variant="secondary"
        size="sm"
      >
        採購
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-2xl grid-rows-[auto_auto_minmax(0,1fr)_auto] max-h-[90dvh]">

      <DialogHeader>
        <DialogTitle>採購資訊</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <div class="shrink-0 flex items-center gap-2">
        <Select v-model="form.purchase_order_id">
          <WhenVisible data="purchaseOrders">
            <template #fallback>
              <Loader2 class="mx-auto my-4 w-4 h-4 animate-spin" />
            </template>

            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
          </WhenVisible>

          <SelectContent>
            <SelectItem :value="null">
              [新增進貨單]
            </SelectItem>

            <SelectItem
              v-for="(option, index) in options.filter(o => o.location_id === order.location_id)"
              :key="index"
              :value="option.id"
            >
              {{ option.order_number }}{{ option.remark ? ` - ${option.remark}` : '' }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Input
          v-if="!form.purchase_order_id"
          id="remark"
          v-model="form.remark"
          placeholder="備註"
        />
      </div>

      <div class="overflow-y-auto min-h-0 rounded-md">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[75%]">商品項目</TableHead>
              <TableHead class="text-center">數量</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="(item, index) in form.items.filter(i => i.max_quantity > 0)"
              :key="`purchase-request-item-${index}-${item.id}`"
            >
              <TableCell>
                <div class="flex items-center gap-2">
                  <Checkbox
                    :id="`item-${index}`"
                    @update:modelValue="val => item.quantity = (val ? item.max_quantity : 0)"
                  />
                  <label
                    :for="`item-${index}`"
                    class="cursor-pointer"
                  >
                    {{ item.product_name }}
                  </label>
                </div>
              </TableCell>
              <TableCell>
                <div class="relative">
                  <Input
                    v-model="item.quantity"
                    :disabled="item.quantity === 0"
                    type="number"
                    min="1"
                    :max="item.max_quantity"
                    class="pr-10"
                  />
                  <span class="absolute top-2 right-2 text-sm text-muted-foreground">
                    of {{ item.max_quantity || 0 }}
                  </span>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <DialogFooter class="flex items-center">
        <InputError :message="form.errors.items" />
        <Button
          type="submit"
          :disabled="form.processing"
          @click="onPurchase"
        >
          採購
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
