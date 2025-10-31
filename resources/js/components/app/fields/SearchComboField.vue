<script setup>
import { Input } from '@/components/ui/input'
import { Search } from 'lucide-vue-next'

const props = defineProps({
  selectedField: {
    type: String,
    required: true
  },
  fieldValue: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:selectedField', 'update:fieldValue'])

const getKeywordLabel = () => {
  const current = props.options?.find(o => o.value === props.selectedField)
  return current?.label || ''
}
</script>

<template>
  <div class="flex relative w-[250px] lg:w-[350px]">
    <div class="absolute inset-y-0 left-0 w-[120px]">
      <select
        :value="selectedField"
        class="h-full w-full rounded-l-md border border-r-0 border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-0"
        @change="emit('update:selectedField', $event.target.value)"
      >
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>
    </div>
    <div class="w-full">
      <Input
        :model-value="fieldValue"
        :placeholder="`搜尋 ${getKeywordLabel()}`"
        class="h-8 pl-[120px] pr-8"
        @update:modelValue="val => emit('update:fieldValue', val)"
      />
      <button
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer focus:outline-none"
      >
        <Search class="h-4 w-4" />
      </button>
    </div>
  </div>
</template> 