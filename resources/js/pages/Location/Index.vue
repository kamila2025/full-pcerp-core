<script setup>
import { router } from '@inertiajs/vue3'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppHeader from '@/components/AppHeader.vue'
import { MapPin, Pencil, X, Plus } from 'lucide-vue-next'

// ------------------------------------------------
const props = defineProps({
  locations: Array,
})

const onDeleting = row => {
  if (confirm('確定要刪除嗎？')) {
    router.delete(`locations/${row.id}`)
  }
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="`地址設定`"
  >
    <template #actions>
      <Button
        v-if="locations.length > 0"
        variant="outline"
        size="icon"
        @click="router.get(`locations/create`)"
      >
        <Plus />
      </Button>
    </template>
  </AppHeader>

  <div class="flex flex-col md:flex-row gap-8">
    <!-- 左側說明區塊 -->
    <div class="w-full md:w-1/3">
      <h3 class="font-semibold mb-2">據點地址</h3>
      <p class="text-sm text-gray-500">
        可以設置為門市、倉庫或其他地點作為品牌的據點。
      </p>
    </div>

    <!-- 右側內容操作區塊 -->
    <div class="w-full md:w-2/3">
      <div
        v-if="locations.length === 0"
        class="border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center py-16"
      >
        <MapPin class="w-12 h-12 text-gray-400 mb-4" />

        <p class="text-gray-500 mb-4">您尚未有任何取貨地址設定</p>

        <Button
          variant="default"
          @click="router.get(`locations/create`)"
        >
          新增取貨地址
        </Button>
      </div>

      <div
        v-else
        class="bg-white p-4 rounded-lg shadow"
      >
        <div
          v-for="(location, index) in locations"
          :key="index"
          class="flex items-start justify-between border-b py-4 last:border-none"
        >
          <div class="flex items-start space-x-3">
            <MapPin class="w-6 h-6 text-gray-500" />
            <div>
              <div class="flex items-center space-x-2">
                <span class="text-lg font-bold">{{ location.name }}</span>
                <Badge
                  v-if="location.is_primary"
                  variant="success"
                >主要地址</Badge>
              </div>
              <p class="text-gray-500">{{ location.address1 }}</p>
            </div>
          </div>

          <div>
            <Button
              variant="ghost"
              size="icon"
              @click="router.get(`locations/${location.id}/edit`)"
            >
              <Pencil class="w-4 h-4 text-green-500" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="text text-right"
              @click="onDeleting(location)"
            >
              <X class="w-4 h-4 text-red-500" />
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
