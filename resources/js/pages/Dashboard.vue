<script setup>
import { Package, ShoppingCart, Tag } from 'lucide-vue-next'

const props = defineProps({
  orderToShip: Number,
  orderUnpaid: Number,
  productLowStock: Number,
})
</script>

<template>
  <div class="p-4">
    <div class="bg-muted rounded-xl p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 歡迎訊息 -->
        <div class="flex flex-col justify-center">
          <h3 class="text-lg font-bold">歡迎回來，</h3>
          <p class="text-sm text-muted-foreground">查看目前的業務情形</p>
        </div>

        <!-- 卡片 1 -->
        <a
          href="/orders?search=fulfillment_status:unfulfilled,partially_fulfilled"
          class="bg-white p-4 rounded-xl shadow-sm flex items-center justify-between "
        >
          <div>
            <div class="text-lg font-semibold">{{ orderToShip }}</div>
            <div class="text-sm text-muted-foreground">訂單未出貨</div>
          </div>
          <Package class="w-6 h-6 text-blue-500" />
        </a>

        <!-- 卡片 2 -->
        <a
          href="/orders?search=financial_status:unpaid,partially_paid"
          class="bg-white p-4 rounded-xl shadow-sm flex items-center justify-between"
        >
          <div>
            <div class="text-lg font-semibold">{{ orderUnpaid }}</div>
            <div class="text-sm text-muted-foreground">訂單未付款</div>
          </div>
          <ShoppingCart class="w-6 h-6 text-blue-500" />
        </a>

        <!-- 卡片 3 -->
        <a
          href="/inventories?inventory_quantity_max=0"
          class="bg-white p-4 rounded-xl shadow-sm flex items-center justify-between"
        >
          <div>
            <div class="text-lg font-semibold">{{ productLowStock }}</div>
            <div class="text-sm text-muted-foreground">商品需補貨</div>
          </div>
          <Tag class="w-6 h-6 text-blue-500" />
        </a>
      </div>
    </div>
  </div>

  <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
      <div class="aspect-video rounded-xl bg-muted/50" />
      <div class="aspect-video rounded-xl bg-muted/50" />
      <div class="aspect-video rounded-xl bg-muted/50" />
    </div>
    <div class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min" />
  </div>
</template>
