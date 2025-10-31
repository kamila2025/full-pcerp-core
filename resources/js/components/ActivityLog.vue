<script setup>
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  activities: Array,
})
</script>

<template>
  <div class="bg-white p-6 rounded-xl shadow space-y-6">
    <h2 class="text-lg font-semibold">操作紀錄</h2>

    <!-- Timeline Start -->
    <div class="relative pl-6 border-l border-gray-200 space-y-6">

      <!-- 樹狀子活動 -->
      <div
        v-for="(item, index) in activities"
        :key="index"
        class="relative pl-6"
      >
        <div class="absolute -left-3 top-1 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs">
          ⌛
        </div>
        <div class="flex justify-between">
          <div class="text-sm text-gray-800 leading-6">
            <strong>{{ item.causer?.name }}</strong> {{ item.event === 'updated' && '更新了' || item.event === 'created' && '新增了' || item.description }}資料
            <ul class="list-disc pl-6 mt-2 text-gray-600 text-sm space-y-1">
              <li
                v-for="(value, field) in item.properties.attributes"
                :key="field"
                v-show="value"
              >
                <strong>{{ field }}: </strong> {{ item.properties.old?.[field] || '' }} → {{ value }}
              </li>
            </ul>
          </div>
          <div class="text-xs text-gray-400">{{ moment(item.created_at).fromNow() }}</div>
        </div>
      </div>

    </div>
  </div>
</template>
