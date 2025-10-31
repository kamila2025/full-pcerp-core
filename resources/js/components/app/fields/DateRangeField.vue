<script setup>
import moment from 'moment'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => ['', '']
  },
  label: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const updateDateRange = (fromValue, toValue) => {
  // 使用 moment 轉換日期為時間戳
  const fromTimestamp = fromValue ? moment(fromValue).startOf('day').unix() : ''
  const toTimestamp = toValue ? moment(toValue).endOf('day').unix() : ''
  
  emit('update:modelValue', [fromTimestamp, toTimestamp])
}
</script>

<template>
  <div class="w-[350px] text-sm">
    <div class="flex h-8 border border-input bg-background rounded-md overflow-hidden">
      <!-- Label 區塊 -->
      <div class="min-w-[80px] bg-muted text-muted-foreground px-3 flex items-center justify-between border-r border-input truncate">
        {{ label }}
      </div>

      <!-- 日期輸入區塊 -->
      <div class="flex-1 flex items-center gap-1 px-2">
        <input
          type="date"
          :value="modelValue[0] ? moment.unix(modelValue[0]).format('YYYY-MM-DD') : ''"
          @input="e => updateDateRange(e.target.value, modelValue[1] ? moment.unix(modelValue[1]).format('YYYY-MM-DD') : '')"
          class="w-full h-full bg-transparent border-none focus:outline-none appearance-none"
        />
        <span class="text-gray-400">~</span>
        <input
          type="date"
          :value="modelValue[1] ? moment.unix(modelValue[1]).format('YYYY-MM-DD') : ''"
          @input="e => updateDateRange(modelValue[0] ? moment.unix(modelValue[0]).format('YYYY-MM-DD') : '', e.target.value)"
          class="w-full h-full bg-transparent border-none focus:outline-none appearance-none"
        />
      </div>
    </div>
  </div>
</template>
