<script setup>
import { ref, computed } from 'vue'
import { Textarea } from '@/components/ui/textarea'
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
  modelValue: { type: [String, Number], required: false },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  rows: { type: null, default: 3 },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const modelValue = ref(props.modelValue)

const submit = () => {
  open.value = false

  emit('update:modelValue', modelValue.value)

  modelValue.value = null
}

const fetchData = () => modelValue.value = props.modelValue
</script>

<template>
  <Dialog
    v-model:open="open"
    @update:open="fetchData"
  >
    <DialogTrigger as-child>
      <slot />
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ label }}</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit">
        <Textarea
          v-model="modelValue"
          :placeholder="placeholder"
          :rows="4"
        />

        <div class="text-right mt-4">
          <Button type="submit">儲存</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
