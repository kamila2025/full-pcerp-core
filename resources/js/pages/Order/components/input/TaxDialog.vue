<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
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
import { Trash } from 'lucide-vue-next'

const props = defineProps({
  taxRate: { type: Number, required: false },
  taxIncluded: { type: Boolean, required: false },
})

const emit = defineEmits([
  'update:taxRate',
  'update:taxIncluded',
])

const open = ref(false)

const taxRate = ref(props.taxRate || 0)

const taxIncluded = ref(props.taxIncluded || false)

const submit = () => {
  open.value = false

  emit('update:taxRate', taxRate.value)

  emit('update:taxIncluded', taxIncluded.value)
}

const onDelete = () => {
  open.value = false

  taxRate.value = null

  taxIncluded.value = false

  emit('update:taxRate', taxRate.value)

  emit('update:taxIncluded', taxIncluded.value)
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
        <div class="space-y-4">
          <!-- 稅率百分比 -->
          <div class="relative">
            <Input
              v-model="taxRate"
              type="number"
              min="0"
              placeholder="5"
              class="pr-10"
            />
            <span class="absolute top-2.5 right-3 text-sm text-muted-foreground">%</span>
          </div>

          <!-- 是否含運費 -->
          <!-- <label class="flex items-center gap-2 text-sm">
            <Checkbox
              id="taxIncluded"
              v-model="taxIncluded"
            />
            <label for="taxIncluded">
              含稅
            </label>
          </label> -->
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Button
            v-if="taxRate"
            type="button"
            variant="outline"
            size="icon"
            @click="onDelete"
          >
            <Trash class="w-4 h-4" />
          </Button>

          <Button type="submit">儲存</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
