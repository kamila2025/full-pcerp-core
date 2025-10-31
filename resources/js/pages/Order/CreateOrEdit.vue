<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu'
import { CustomerDialog, AddCustomerDialog, TextareaDialog, UserDialog, ShippingDialog, LocationDialog } from './components/dialog'
import { OrderCard, OrderHistoryCard } from './components/card'
import { Eye, CirclePlus, CircleMinus, Pencil, PlusIcon, Receipt, File, Package, Truck, Box, MapPin, Trash2, UserIcon, Ellipsis, RotateCcw, EyeOff, Archive, FileText, Save, ChevronDown, CheckCircle2, FileEdit } from 'lucide-vue-next'
import OrderItemTable from './components/OrderItemTable.vue'
import OrderSummary from './components/OrderSummary.vue'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'

// ------------------------------------------------
const props = defineProps({
  order: Object,
  template: Object,
  variants: Array,
  gatewaies: Array,
  services: null,
  logistics: Array,
  companies: Object,
  //
  params: Object,
})

// 隱藏價格
const hidePrices = ref(false)

// 隱藏成本
const hideCostPrice = ref(true)

// 僅顯示總結
const showSummaryOnly = ref(false)

// 表單
const form = useForm({
  template_id: props.template?.id || null,
  attribution_user: props.order?.attribution_user || null,
  location: props.order?.location || null,
  customer: props.order?.customer || null,
  delivery_type: props.order?.delivery_type || null,
  total_discount: props.order?.total_discount || null,
  total_shipping: props.order?.total_shipping || null,
  total_tax: props.order?.total_tax || null,
  custom_amount: props.order?.custom_amount || null,
  amount: props.order?.total_price !== props.order?.amount ? props.order?.amount : null,
  note: props.order?.note || null,
  remark: props.order?.remark || null,
  items: props.order?.items || [],
  shipping_address: props.order?.shipping_address || null,
  pickup_address: props.order?.pickup_address || null,
  shipping_fees: props.order?.shipping_fees || [],
  tax_rate: props.order?.tax_rate || null,
  tax_included: props.order?.tax_included || null,
  transactions: props.order?.transactions || [],
  shipping_location: props.order?.shipping_location || null,
})

// 提交表單
const submit = ({ status }) => {
  if (props.order) {
    form.transform(data => ({ ...data, status })).put(`/orders/${props.order.id}`)
  } else {
    form.transform(data => ({ ...data, status })).post('/orders')
  }
}

// 封存訂單
const onArchive = () => {
  if (!confirm('確認封存訂單？')) return

  router.put(`/orders/${props.order.id}/status`, { status: 'archived' })
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="order ? order.order_number : `新增報價單`"
    is-back
    @back-action="router.get('/orders')"
  >
    <template #actions>
      <div class="space-x-1 flex">
        <DropdownMenu v-if="order">
          <DropdownMenuTrigger as-child>
            <Button
              variant="outline"
              size="icon"
            >
              <Ellipsis class="w-5 h-5" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem
              as="a"
              :href="`/orders/${order.id}/slip`"
              target="_blank"
            >
              裝箱單
            </DropdownMenuItem>
            <DropdownMenuItem
              as="a"
              :href="`/orders/${order.id}/preview`"
              target="_blank"
            >
              訂單明細
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="onArchive">
              <Archive class="h-4 w-4" />
              封存訂單
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- 新增訂單時的儲存選項 -->
        <div class="inline-flex">
          <Button
            variant="secondary"
            :disabled="form.processing"
            :class="{ 'rounded-r-none border-r-0': !order }"
            @click="submit"
          >
            Save
          </Button>

          <DropdownMenu v-if="!order">
            <DropdownMenuTrigger asChild>
              <Button
                variant="secondary"
                class="rounded-l-none px-2 border-l-[0.5px] border-l-border"
              >
                <ChevronDown class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem
                @click="submit({ status: 'draft' })"
                class="flex items-center"
              >
                <FileEdit class="w-4 h-4 mr-1 text-yellow-500" />
                <span>儲存為草稿</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </template>
  </AppHeader>

  <!-- 草稿狀態橫幅 -->
  <div
    v-if="order?.status === 'draft'"
    class="bg-yellow-50 border-l-4 border-yellow-400 p-4 sticky top-16 z-40"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 max-w-screen-2xl mx-auto">
      <div class="flex items-center gap-3">
        <FileText class="h-5 w-5 flex-shrink-0 text-yellow-400" />
        <p class="text-sm text-yellow-700">
          此訂單目前為草稿狀態，尚未正式建立
        </p>
      </div>
      <Button
        variant="default"
        size="sm"
        class="bg-yellow-500 hover:bg-yellow-600 text-white w-full sm:w-auto"
        @click="submit({ status: 'open' })"
      >
        轉為處理中
      </Button>
    </div>
  </div>

  <!-- 封存狀態橫幅 -->
  <div
    v-if="order?.status === 'archived'"
    class="bg-gray-50 border-l-4 border-gray-400 p-4 sticky top-16 z-40"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 max-w-screen-2xl mx-auto">
      <div class="flex items-center gap-3">
        <Archive class="h-5 w-5 flex-shrink-0 text-gray-400" />
        <p class="text-sm text-gray-700">
          此訂單已封存
        </p>
      </div>
    </div>
  </div>

  <div class="flex items-center justify-center ml-auto">
    <Button
      variant="ghost"
      class="p-2"
      @click="hidePrices = !hidePrices"
    >
      <component :is="hidePrices ? EyeOff : Eye" />
      <span>{{ hidePrices ? '顯示價格' : '隱藏價格' }}</span>
    </Button>
    <Button
      variant="ghost"
      class="p-2"
      @click="hideCostPrice = !hideCostPrice"
    >
      <component :is="hideCostPrice ? EyeOff : Eye" />
      <span>{{ hideCostPrice ? '顯示成本' : '隱藏成本' }}</span>
    </Button>
    <Button
      variant="ghost"
      class="p-2"
      @click="showSummaryOnly = !showSummaryOnly"
    >
      <component :is="showSummaryOnly ? EyeOff : Eye" />
      <span>{{ showSummaryOnly ? '顯示所有' : '僅顯示明細' }}</span>
    </Button>
  </div>

  <div class="grid grid-cols-3 gap-2 lg:gap-4">
    <div class="col-span-3 lg:col-span-2 space-y-2">
      <div class="rounded-2xl border bg-white p-4 shadow-sm space-y-2">
        <OrderCard
          v-if="order"
          :order="order"
          :gatewaies="gatewaies"
          :services="services"
          :companies="companies"
        />

        <!-- 出貨地點選擇 -->
        <div>
          <LocationDialog
            :hide-action="true"
            :model-value="form.shipping_location"
            @update:model-value="option => form.shipping_location = option"
          >
            <Button variant="outline">
              <Package class="w-4 h-4 mr-2 text-muted-foreground" />
              <span>選擇出貨地點</span>
            </Button>

            <template #selected-label>
              <div class="flex items-center gap-2">
                <Package class="w-4 h-4 text-muted-foreground" />
                <div class="text-foreground font-semibold">
                  {{ form.shipping_location?.name ? `從 ${form.shipping_location.name} 出貨` : '選擇出貨地點' }}
                </div>
              </div>
            </template>
          </LocationDialog>
          <InputError :message="form.errors['shipping_location.id']" />
        </div>

        <!-- 訂單明細 -->
        <OrderItemTable
          v-model="form.items"
          :hide-prices="hidePrices"
          :hide-cost-price="hideCostPrice"
          :show-summary-only="showSummaryOnly"
          :no-template="Boolean(template) === false"
          :template-items="template?.items"
          :shipping-location="form.shipping_location"
        />

        <div class="mb-2">
          <OrderSummary
            v-model:total-discount="form.total_discount"
            v-model:shipping-fees="form.shipping_fees"
            v-model:tax-rate="form.tax_rate"
            v-model:custom-amount="form.custom_amount"
            :form="form"
            :items="form.items"
            :logistics="logistics"
            class="mb-4"
          />
        </div>

        <hr>

        <div class="space-y-2">
          <Label for="remark">顧客備註欄</Label>
          <Textarea
            id="remark"
            v-model="form.remark"
            rows="4"
            class="rounded-lg"
          />
        </div>
      </div>

      <OrderHistoryCard
        v-if="order"
        :gatewaies="gatewaies"
        :companies="companies"
        :transactions="order.transactions"
        :fulfillments="order.fulfillments"
      />
    </div>

    <div class="col-span-3 lg:col-span-1 space-y-2">
      <!-- 顧客資訊 -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center gap-1">
            <UserIcon class="w-4 h-4 text-muted-foreground" />
            顧客資訊
          </CardTitle>
        </CardHeader>

        <CardContent class="flex flex-col items-start">
          <CustomerDialog
            v-model="form.customer"
            :customer-id="params?.customer_id"
          >
            <Button
              variant="link"
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <PlusIcon class="w-4 h-4" />
              <span>選擇現有顧客</span>
            </Button>
          </CustomerDialog>

          <AddCustomerDialog
            v-if="!form.customer"
            v-model="form.customer"
          >
            <Button
              variant="link"
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <PlusIcon class="w-4 h-4" />
              <span>新增顧客</span>
            </Button>
          </AddCustomerDialog>
        </CardContent>
      </Card>

      <!-- 收件 / 取貨 -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center gap-1">
            <Package class="w-4 h-4 text-muted-foreground" />
            <span v-if="form.delivery_type === 'shipping'">宅配地址</span>
            <span v-else>收件 / 取貨</span>
          </CardTitle>
        </CardHeader>

        <CardContent class="flex flex-col items-start">
          <ShippingDialog
            v-model="form.shipping_address"
            @update:modelValue="() => {
              form.delivery_type = 'shipping';
              form.reset('pickup_address');
            }"
          >
            <Button
              variant="link"
              :class="{ 'order-1': form.shipping_address }"
              class="order-1 p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <template v-if="!form.delivery_type || form.delivery_type === 'others'">
                <PlusIcon class="w-4 h-4" />
                <span>新增收件地址</span>
              </template>
              <template v-else>
                <RotateCcw class="w-4 h-4" />
                <span>變更為收件地址</span>
              </template>
            </Button>
          </ShippingDialog>

          <LocationDialog
            :model-value="form.pickup_address?.location"
            :deletable="false"
            @update:model-value="option => {
              form.delivery_type = 'pickup';
              form.pickup_address = { location: option };
              form.reset('shipping_address');
            }"
          >
            <Button
              variant="link"
              :class="{ 'order-1': form.pickup_address }"
              class="order-1 p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <template v-if="!form.delivery_type || form.delivery_type === 'others'">
                <PlusIcon class="w-4 h-4" />
                <span>加入取貨地址</span>
              </template>
              <template v-else>
                <RotateCcw class="w-4 h-4" />
                <span>變更為取貨</span>
              </template>
            </Button>
          </LocationDialog>

          <Button
            v-if="form.delivery_type && form.delivery_type !== 'others'"
            variant="link"
            class="order-2 p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            @click="() => {
              form.delivery_type = null
              form.shipping_address = null
              form.pickup_address = null
            }"
          >
            <Trash2 class="w-4 h-4" />
            <span>移除</span>
          </Button>
        </CardContent>
      </Card>

      <!-- 商家備註欄 -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="flex justify-between items-center">
            <div class="flex items-center gap-1">
              <File class="w-4 h-4 text-muted-foreground" />
              商家備註欄
            </div>
            <TextareaDialog
              v-model="form.note"
              label="商家備註欄"
              placeholder="留下備註...."
            >
              <Pencil class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer" />
            </TextareaDialog>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          {{ form.note || '留下備註....' }}
        </CardContent>
      </Card>

      <!-- 門市銷售認列 -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center gap-1">
            <Receipt class="w-4 h-4 text-muted-foreground" />
            <span>門市銷售認列</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <UserDialog v-model="form.attribution_user">
            <Button
              variant="link"
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <PlusIcon class="w-4 h-4" />
              <span>新增銷售員</span>
            </Button>
          </UserDialog>

          <hr>

          <LocationDialog
            :model-value="form.location"
            :editable="false"
            @update:model-value="option => form.location = option"
          >
            <Button
              variant="link"
              class="p-0 text-muted-foreground hover:text-foreground hover:no-underline"
            >
              <PlusIcon class="w-4 h-4" />
              <span>分配地點</span>
            </Button>
          </LocationDialog>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
