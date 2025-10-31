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
  SelectItem
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Package, Tag } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import moment from 'moment'
import PaymentDialog from '../action/PaymentDialog.vue'
import PurchaseDialog from '../action/PurchaseDialog.vue'
import OrderShipDialog from '../action/OrderShipDialog.vue'

const props = defineProps({
  order: { type: null },
})
</script>

<template>
  <div class="rounded-2xl border bg-white p-5 shadow-sm relative">
    <span class="text-xs text-muted-foreground absolute top-2 right-2">{{ moment(order.created_at).format('YYYY-MM-DD HH:mm') }}</span>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <!-- 訂單總金額 -->
      <div class="space-y-2">
        <h3 class="text-sm text-muted-foreground">訂單總金額</h3>
        <div class="text-2xl font-bold text-foreground">
          {{ order.amount.toLocaleString() }} TWD
        </div>
        <div class="text-xs text-muted-foreground">
          {{ order.transactions?.filter(t => t.status === 'success').reduce((a, b) => a + b.amount, 0)?.toLocaleString() }} 已付款,
          {{ order.transactions?.filter(t => t.status !== 'success').reduce((a, b) => a + b.amount, 0)?.toLocaleString() }} 處理中
        </div>

        <PaymentDialog :order="order" />
      </div>

      <!-- 商品總數 -->
      <div class="space-y-2">
        <h3 class="text-sm text-muted-foreground">商品總數</h3>
        <div class="text-2xl font-bold text-foreground">
          {{ order.items?.reduce((a, b) => a + b.quantity, 0) }}
        </div>
        <div class="text-xs text-muted-foreground">
          {{ order.fulfilled_items?.filter(t => t.fulfillment.status === 'fulfilled').reduce((a, b) => a + b.fulfilled_quantity, 0) || 0 }} 已出貨
        </div>

        <div class="flex gap-2">
          <!-- 出貨 -->
          <OrderShipDialog
            v-if="order.items.reduce((a, b) => a + b.quantity, 0) > order.fulfilled_items.filter(t => t.fulfillment.status === 'fulfilled').reduce((a, b) => a + b.fulfilled_quantity, 0)"
            :order="order"
          />

          <!-- 採購 -->
          <PurchaseDialog :order="order" />
        </div>
      </div>
    </div>
  </div>
</template>
