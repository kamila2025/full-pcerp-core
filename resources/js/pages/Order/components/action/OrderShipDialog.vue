<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
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
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Package, Tag } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  order: { type: null },
})

const isOpen = ref(false)

const services = ref({})

const companies = ref({})

const form = useForm({
  service: null,
  tracking_company: null,
  tracking_number: null,
  items: props.order.items.map(item => ({
    order_id: item.id,
    product_name: item.product_name,
    fulfillable_quantity: item.quantity - item.fulfillment_items.reduce((sum, i) => sum + i.fulfilled_quantity, 0),
    quantity: item.quantity - item.fulfillment_items.reduce((sum, i) => sum + i.fulfilled_quantity, 0),
  }))
})

const onFulfillShip = () => {
  form.post(`/orders/${props.order.id}/fulfillments`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.location.reload()
    },
    onError: () => {
      console.error('出貨失敗')
    }
  })
}

const fetchData = open => {
  router.reload({
    only: ['services', 'companies'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      services.value = page.props?.services || {}

      companies.value = page.props?.companies || {}
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
        出貨
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-2xl grid-rows-[auto_minmax(0,1fr)_auto] max-h-[90dvh]">
      <DialogHeader>
        <DialogTitle>出貨資訊</DialogTitle>
      </DialogHeader>
      <div class="grid gap-4 py-4 overflow-y-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="col-span-2">
            <Label for="service">服務商</Label>
            <div class="relative">
              <Select v-model="form.service">
                <SelectTrigger id="gateway_id">
                  <SelectValue placeholder="選擇服務商" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="(service, index) in services"
                    :key="index"
                    :value="index"
                  >
                    {{ service }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.service" />
            </div>
          </div>

          <div
            v-if="form.service === 'manual'"
            class="col-span-2 lg:col-span-1"
          >
            <Label for="tracking_company">物流服務</Label>
            <div class="relative">
              <Select
                id="tracking_company"
                v-model="form.tracking_company"
              >
                <SelectTrigger id="gateway_id">
                  <SelectValue placeholder="選擇物流服務" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="(name, value) in companies"
                    :key="value"
                    :value="value"
                  >
                    {{ name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.tracking_company" />
            </div>
          </div>

          <div
            v-if="form.service === 'manual'"
            class="col-span-2 lg:col-span-1"
          >
            <Label for="tracking_number">追蹤編號</Label>
            <div class="relative">
              <Input
                id="tracking_number"
                v-model="form.tracking_number"
              />
              <InputError :message="form.errors.tracking_number" />
            </div>
          </div>
        </div>

        <div class="mt-4 space-y-4">
          <!-- 表頭 -->
          <div class="grid grid-cols-12 text-xs text-muted-foreground px-2">
            <div class="col-span-9">商品項目</div>
            <div class="col-span-3 text-center">數量</div>
          </div>

          <!-- 預設物流 -->
          <div class="flex items-center text-sm px-2 gap-2 text-muted-foreground">
            <Package class="w-4 h-4" />
            <span>預設物流（一般包裹）</span>
          </div>

          <!-- 商品列表 -->
          <div
            v-for="(item, index) in form.items"
            :key="item.id"
            class="grid grid-cols-12 items-center gap-2 px-2 py-2 border-b"
          >
            <div class="col-span-8 flex items-center gap-2 text-sm">
              <Tag class="w-4 h-4 text-muted-foreground" />
              {{ item.product_name }}
            </div>
            <div class="col-span-4">
              <div class="relative">
                <Input
                  type="number"
                  v-model="item.quantity"
                  class="pr-14 text-center"
                />
                <span class="absolute right-2 top-1.5 text-xs text-muted-foreground">
                  of {{ item.fulfillable_quantity }}
                </span>
                <InputError :message="form.errors[`items.${index}.quantity`]" />
              </div>
            </div>
          </div>
          <InputError :message="form.errors.items" />
        </div>

        <!-- 底部操作 -->
        <div class="text-right">
          <Button @click="onFulfillShip">出貨</Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
