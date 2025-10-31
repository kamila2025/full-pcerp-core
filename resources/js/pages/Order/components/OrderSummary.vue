<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { CustomerDialog, AddCustomerDialog, ShippingDialog, TextareaDialog, UserDialog, LocationDialog } from '../components/dialog'
import { OrderCard, OrderHistoryCard } from '../components/card'
import { InputDialog, DiscountDialog, ShippingFeeDialog, TaxDialog } from '../components/input'
import { Eye, CirclePlus, CircleMinus, Pencil, PlusIcon, Receipt, File, Package, Trash2, UserIcon, Ellipsis } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  totalDiscount: Number,
  shippingFees: Array,
  taxRate: Number,
  customAmount: Number,
  form: Object,
  logistics: Array,
})

const emit = defineEmits([
  'update:shippingFees',
  'update:totalDiscount',
  'update:taxRate',
  'update:customAmount',
])

// 運費
const shippingFees = computed({
  get: () => props.shippingFees || [],
  set: val => emit('update:shippingFees', val),
})

// 稅率
const taxRate = computed({
  get: () => props.taxRate || 0,
  set: val => emit('update:taxRate', val),
})

// 自訂金額
const customAmount = computed({
  get: () => props.customAmount || 0,
  set: val => emit('update:customAmount', val),
})

// 總計小計
const subtotalAmount = computed(() => props.form.items.reduce((total, item) => total + (item.price * item.quantity), 0))

// 總計成本
const subtotalCostAmount = computed(() => props.form.items.reduce((total, item) => total + (item.cost_price * item.quantity), 0))

// 總計折扣
const totalDiscount = computed({
  get: () => props.totalDiscount,
  set: val => emit('update:totalDiscount', val),
})

// 總計運費
const totalShipping = computed({
  get: () => shippingFees.value?.reduce((sum, item) => sum + item.price, 0) || 0,
})

// 總計稅金
const totalTax = computed({
  get: () => {
    if (!taxRate.value) return 0

    const baseAmount = customAmount.value ? customAmount.value : (subtotalAmount.value - totalDiscount.value + totalShipping.value)

    return Math.round(baseAmount * 1.05) - baseAmount
  },
})

// 計算總金額
const totalPrice = computed(() => {
  const baseAmount = subtotalAmount.value - totalDiscount.value + totalShipping.value

  return Math.round(baseAmount * (1 - taxRate.value / 100))
})

// 計算總金額
const totalAmount = computed(() => (customAmount.value ? (customAmount.value + totalTax.value) : totalPrice.value) - totalTax.value)

//
const totalAmountIncluded = computed(() => totalAmount.value + totalTax.value)

//
const totalTransactionFee = computed({
  get: () => props.form.transactions?.reduce((sum, item) => sum + item.fee, 0) || 0,
})

//
const totalProfit = computed({
  get: () => totalAmount.value - subtotalCostAmount.value - totalTransactionFee.value - totalShipping.value
})
</script>

<template>
  <div class="flex flex-col gap-2 text-sm text-muted-foreground">
    <div class="flex justify-between text-foreground">
      <div class="text-right flex-none sm:flex-auto">小計</div>
      <div class="text-right w-3/12">
        {{ subtotalAmount.toLocaleString() }} TWD
      </div>
    </div>
    <hr class="text-right w-full sm:w-5/12 ml-auto">
    <div class="flex justify-between text-foreground">
      <div
        v-if="totalDiscount"
        class="text-right flex-none sm:flex-auto"
      >
        優惠折扣
      </div>
      <DiscountDialog v-model="totalDiscount">
        <button class="text-right ml-auto w-3/12 hover:underline">{{ totalDiscount ? `- ${totalDiscount.toLocaleString()} TWD` : '新增折扣' }}</button>
      </DiscountDialog>
    </div>
    <div class="flex justify-between text-foreground">
      <div
        v-if="shippingFees.length > 0"
        class="text-right flex-none sm:flex-auto"
      >
        運費
      </div>
      <ShippingFeeDialog
        v-model="shippingFees"
        :options="logistics"
      >
        <button class="text-right ml-auto w-3/12 hover:underline">{{ totalShipping ? `${totalShipping.toLocaleString()} TWD` : '新增運費' }}</button>
      </ShippingFeeDialog>
    </div>
    <div class="flex justify-between text-foreground">
      <div
        v-if="taxRate"
        class="text-right flex-none sm:flex-auto"
      >
        稅金
        <span class="text-muted-foreground">({{ taxRate }}%)</span>
      </div>

      <TaxDialog v-model:tax-rate="taxRate">
        <button class="text-right ml-auto w-3/12 hover:underline">
          {{ totalTax ? `${totalTax.toLocaleString()} TWD` : '新增稅金' }}
        </button>
      </TaxDialog>
    </div>
    <hr class="text-right w-full sm:w-5/12 ml-auto">
    <div class="flex justify-between text-foreground font-semibold">
      <div class="text-right flex-none sm:flex-auto">訂單售價<span :class="['text-xs', { hidden: !Boolean(taxRate) }]">(未稅)</span></div>
      <div class="text-right w-4/12 sm:w-3/12">
        <InputDialog v-model="customAmount">
          <button class="hover:underline">
            <div class="flex items-center gap-1 justify-end">
              <Pencil class="w-4 h-4 text-muted-foreground hover:text-foreground" />
              <span class="text-red-600">{{ totalAmount.toLocaleString() }}</span>
              <span>TWD</span>
            </div>
          </button>
          <template #description>
            <p class="text-xs text-gray-500 mt-1">
              有輸入內容才會使用自訂金額，否則將使用系統自動計算。
            </p>
          </template>
          <template #title>
            自訂總金額
          </template>
        </InputDialog>
        <div
          v-if="customAmount"
          class="font-normal text-muted-foreground hover:no-underline text-xs"
        >
          <s>{{ totalPrice.toLocaleString() }} TWD</s>
        </div>
      </div>
    </div>
    <div
      v-if="taxRate"
      class="flex justify-between text-foreground font-semibold"
    >
      <div class="text-right flex-none sm:flex-auto">訂單售價<span class="text-xs">(含稅)</span></div>
      <div class="text-right w-4/12 sm:w-3/12 flex items-center gap-1 justify-end">
        <span class="text-red-600">{{ totalAmountIncluded.toLocaleString() }}</span>
        <span>TWD</span>
      </div>
    </div>
    <hr class="text-right w-full sm:w-5/12 ml-auto">
    <div class="flex justify-between text-muted-foreground text-xs">
      <div class="text-right flex-none sm:flex-auto">成本</div>
      <div class="text-right w-3/12">
        ({{ subtotalCostAmount.toLocaleString() }}) TWD
      </div>
    </div>
    <div
      v-if="taxRate"
      class="flex justify-between text-muted-foreground text-xs"
    >
      <div class="text-right flex-none sm:flex-auto">營業稅</div>
      <div class="text-right w-3/12">
        ({{ totalTax.toLocaleString() }}) TWD
      </div>
    </div>
    <div
      v-if="totalShipping"
      class="flex justify-between text-muted-foreground text-xs"
    >
      <div class="text-right flex-none sm:flex-auto">物流費</div>
      <div class="text-right w-3/12">
        ({{ totalShipping.toLocaleString() }}) TWD
      </div>
    </div>
    <div
      v-if="totalTransactionFee"
      class="flex justify-between text-muted-foreground text-xs"
    >
      <div class="text-right flex-none sm:flex-auto">交易手續費</div>
      <div class="text-right w-3/12">
        ({{ totalTransactionFee.toLocaleString() }}) TWD
      </div>
    </div>
    <div class="flex justify-between text-muted-foreground text-xs">
      <div class="text-right flex-none sm:flex-auto">預估利潤</div>
      <div class="text-right w-3/12 flex items-center gap-1 justify-end">
        {{ totalProfit.toLocaleString() }} TWD
      </div>
    </div>
  </div>
</template>
