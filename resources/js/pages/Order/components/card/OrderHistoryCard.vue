<script setup>
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Check, Clock, X } from 'lucide-vue-next'
import TextLink from '@/components/TextLink.vue'
import moment from 'moment'

const props = defineProps({
  gatewaies: { type: Array, default: () => [] },
  companies: { type: Object, default: () => [] },
  transactions: { type: Array, default: () => [] },
  fulfillments: { type: Array, default: () => [] },
})

const copyToClipboard = shortCode => {
  navigator.clipboard.writeText(`${window.location.origin}/t/${shortCode}`)

  alert('已複製付款連結')
}
</script>

<template>
  <div
    v-if="transactions.length > 0"
    class="rounded-2xl border bg-white p-4 shadow-sm"
  >
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h2 class="text-lg font-semibold">
          交易記錄
        </h2>
      </div>
    </div>

    <!-- Records -->
    <div
      v-for="(item, index) in transactions"
      :key="index"
      class="flex items-center justify-between py-3 border-t text-sm"
    >
      <div>
        <div class="font-medium text-foreground">
          <div class="flex items-center gap-2">
            <span class="truncate">{{ item.gateway_title }} <small class="text-xs">{{ $t(`gateway.method.${item.gateway_method}`) }}</small></span>
            <a
              v-if="item.short_code && item.status !== 'success'"
              class="text-blue-600 hover:text-blue-800 no-underline cursor-pointer"
              @click.prevent="copyToClipboard(item.short_code)"
            >
              複製連結
            </a>
          </div>
        </div>
        <div
          v-if="item.paid_at"
          class="text-xs text-muted-foreground"
        >
          {{ moment(item.paid_at).format('YYYY-MM-DD hh:mm A') }}
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="font-semibold text-foreground">
          NT$ {{ item.amount.toLocaleString() }}
        </span>
        <Check
          v-if="item.status === 'success'"
          class="w-4 h-4 text-green-500"
        />
        <Clock
          v-else
          class="w-4 h-4 text-yellow-500"
        />
      </div>
    </div>
  </div>

  <div
    v-if="fulfillments.length > 0"
    class="rounded-2xl border bg-white p-4 shadow-sm"
  >
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h2 class="text-lg font-semibold">
          出貨記錄
        </h2>
      </div>
    </div>

    <!-- Records -->
    <div
      v-for="(item, index) in fulfillments"
      :key="index"
      class="flex items-center justify-between py-3 border-t text-sm"
    >
      <div>
        <div class="font-medium text-foreground">
          手動出貨
          {{ item.tracking_company }} {{ item.tracking_number }}
        </div>
        <div class="text-xs text-muted-foreground">
          {{ moment(item.created_at).format('YYYY-MM-DD hh:mm A') }}
        </div>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm text-muted-foreground">
          {{ item.items.reduce((sum, item) => sum + item.fulfilled_quantity, 0) }} 件商品
        </span>
        <Check
          v-if="item.status === 'fulfilled'"
          class="w-4 h-4 text-green-500"
        />
        <Clock
          v-else
          class="w-4 h-4 text-yellow-500"
        />
      </div>
    </div>
  </div>
</template>
