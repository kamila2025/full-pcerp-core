<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="w-[250px] text-sm">
    <div class="flex h-8 border border-input bg-background rounded-md overflow-hidden">
      <!-- Label 區塊 -->
      <div class="min-w-[80px] bg-muted text-muted-foreground px-3 flex items-center justify-between border-r border-input truncate">
        {{ label }}
      </div>

      <!-- 輸入區塊 -->
      <div class="flex-1 flex items-center px-2">
        <input
          :value="modelValue"
          @input="e => emit('update:modelValue', e.target.value)"
          :placeholder="placeholder"
          class="w-full h-full bg-transparent border-none focus:outline-none"
        />
      </div>
    </div>
  </div>
</template> 