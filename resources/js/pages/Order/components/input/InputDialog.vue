<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

const props = defineProps({
  defaultValue: { type: [String, Number], required: false },
  modelValue: { type: [String, Number], required: false },
  placeholder: { type: String, default: '輸入金額 (TWD)' },
  class: { type: null, required: false },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)

const modelValue = ref(props.modelValue || props.defaultValue)

const submit = () => {
  open.value = false

  emit('update:modelValue', modelValue.value)

  modelValue.value = null
}

watch(open, val => {
  if (!val) return

  nextTick(() => {
    const el = document.activeElement
    if (el && typeof el.blur === 'function') el.blur()
  })
})
</script>

<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <slot />
    </DialogTrigger>

    <DialogContent class="sm:max-w-sm">
      <DialogHeader>
        <DialogTitle>
          <slot name="title">
            輸入金額
          </slot>
        </DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit">
        <slot
          name="form"
          v-bind="{ value: modelValue }"
        >
          <Input
            type="number"
            v-model="modelValue"
            :placeholder="placeholder"
          />
        </slot>

        <slot name="description" />

        <div class="text-right mt-4">
          <Button type="submit">儲存</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
