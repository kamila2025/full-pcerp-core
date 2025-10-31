<script setup>
import { ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  open: Boolean,
})

const emit = defineEmits(['update:open', 'submit'])

// 表單
const form = useForm({
  'product_name': '',
  'price': 0,
  'quantity': 1,
  'cost_price': 0,
})

const confirm = () => {
  if (!form.product_name) {
    form.setError('product_name', '「商品名稱」不能空白')

    return
  }

  emit('submit', form.data())

  emit('update:open', false)

  form.reset()
}

watch(() => props.open, val => {
  if (val) form.reset()
})
</script>

<template>
  <Dialog
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <DialogContent>
      <DialogHeader>
        <DialogTitle>新增商品</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <div class="space-y-4 mt-2">
        <!-- 商品名稱 -->
        <div>
          <label class="block font-semibold mb-1">商品名稱</label>
          <Input
            v-model="form.product_name"
            placeholder="為您的商品命名"
          />

          <InputError :message="form.errors.product_name" />
        </div>

        <!-- 售價、數量、重量 -->
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-semibold mb-1">售價</label>

            <div class="relative w-full">
              <span class="text-sm text-muted-foreground absolute start-0 inset-y-0 flex items-center justify-center px-2">
                TWD
              </span>
              <Input
                v-model="form.price"
                id="price"
                type="number"
                class="pl-12"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold mb-1">數量</label>
            <Input
              v-model="form.quantity"
              type="number"
              min="1"
            />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-sm font-semibold mb-1">成本</label>

          <div class="relative w-full">
            <span class="text-sm text-muted-foreground absolute start-0 inset-y-0 flex items-center justify-center px-2">
              TWD
            </span>
            <Input
              v-model="form.cost_price"
              id="cost_price"
              type="number"
              class="pl-12"
            />
          </div>
        </div>
      </div>

      <DialogFooter class="mt-4">
        <Button @click="confirm">新增</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
