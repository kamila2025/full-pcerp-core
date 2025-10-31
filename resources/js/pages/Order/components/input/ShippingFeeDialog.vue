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
import { Trash } from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu'

const props = defineProps({
  modelValue: { type: Array, required: false },
  options: { type: Array, required: false },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)

const modelValue = ref((props.modelValue || []).sort((a, b) => {
  const aIsNull = a.logistic_id === null;
  const bIsNull = b.logistic_id === null;

  if (aIsNull && !bIsNull) return -1;
  if (!aIsNull && bIsNull) return 1;
  return 0;
}));

if (!props.modelValue || props.modelValue?.length === 0) {
  modelValue.value = [
    {
      price: null,
    },
  ]
}

const submit = () => {
  open.value = false

  emit('update:modelValue', modelValue.value)

  // modelValue.value = null
}

const onDelete = () => {
  open.value = false

  modelValue.value = [
    { price: null },
  ]

  emit('update:modelValue', [])
}

const totalShipping = computed(() => modelValue.value?.reduce((acc, item) => acc + Number(item.price || 0), 0))

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
      </DialogHeader>

      <form
        @submit.prevent="submit"
        class="space-y-2"
      >
        <div
          v-for="(item, index) in modelValue"
          :key="index"
          class="flex items-center gap-2"
        >
          <div class="flex w-full overflow-hidden border rounded-md">
            <!-- 顯示金額 / 編輯金額 -->
            <span
              :class="{ 'bg-gray-50': item.logistic_id }"
              class="border-l flex-1 flex items-center px-3 py-2 text-sm"
            >
              <span v-if="item.logistic_id">
                {{ item.price.toLocaleString() }}
              </span>

              <Input
                v-else
                type="number"
                v-model="item.price"
                min="0"
                placeholder="輸入金額 (TWD)"
                class="border-0 shadow-none focus-visible:ring-0 h-auto p-0"
              />
            </span>

            <!-- 顯示名稱 -->
            <span class="flex items-center px-3 py-2 text-sm w-32 bg-gray-50">
              {{ item.name || '自訂運費' }}
            </span>

            <!-- 刪除按鈕 -->
            <button
              v-if="index > 0"
              type="button"
              @click="modelValue.splice(index, 1)"
              class="px-3 py-2 border-l hover:text-red-500 text-muted-foreground"
            >
              <Trash class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="flex justify-between items-center pt-2">
          <DropdownMenu>
            <DropdownMenuTrigger class="text-sm text-blue-600 hover:underline">
              <span v-if="options && options.length > 0">＋ 新增物流費用</span>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
              <DropdownMenuItem
                v-for="m in options"
                :key="m.id"
                @click="() => modelValue.push({ logistic_id: m.id, name: m.name, price: m.fee })"
              >
                {{ m.name }}（{{ m.fee }}元）
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <div class="text-sm font-semibold">
            總運費：<span class="text-blue-600">{{ totalShipping.toLocaleString() }} 元</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Button
            v-if="props.modelValue?.length > 0"
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
