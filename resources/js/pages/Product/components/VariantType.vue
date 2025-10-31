<script setup>
import { ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'
import { TrashIcon, PlusIcon } from 'lucide-vue-next'
import { VueDraggableNext } from 'vue-draggable-next'
import { GripVertical } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const types = ref(props.modelValue)

watch(types, () => {
  emit('update:modelValue', types.value)
}, { deep: true })
</script>

<template>
  <!-- 規格設置區域 -->
  <VueDraggableNext
    v-model="types"
    item-key="name"
    handle=".drag-handle"
    class="space-y-4"
  >
    <div
      v-for="(type, index) in types"
      :key="index"
      class="space-y-2"
    >
      <div class="flex items-start gap-2 flex-nowrap">
        <!-- 規格名稱 -->
        <div class="flex-none basis-[28%]">
          <div class="text-sm mb-1">規格</div>

          <div class="relative w-full items-center">
            <Input
              v-model="type.name"
              class="w-full pl-6"
            />
            <span class="absolute start-0 inset-y-0 flex items-center justify-center pl-1">
              <GripVertical class="drag-handle size-4 mr-1 cursor-move text-muted-foreground hover:text-foreground" />
            </span>
          </div>
        </div>

        <!-- 規格選項 -->
        <div class="flex-1">
          <div class="text-sm mb-1">選項</div>

          <TagsInput
            :model-value="type.values.map(item => item.name)"
            @add-tag="val => type.values.push({ name: val })"
            @remove-tag="val => type.values = type.values.filter(item => item.name !== val)"
            class="min-w-0"
          >
            <VueDraggableNext
              v-model="type.values"
              item-key="name"
              handle=".drag-handle"
              class="flex flex-wrap gap-2"
            >
              <TagsInputItem
                v-for="item in type.values"
                :key="item.name"
                :value="item.name"
              >
                <GripVertical class="drag-handle size-4 cursor-move text-muted-foreground hover:text-foreground" />
                <TagsInputItemText />
                <TagsInputItemDelete />
              </TagsInputItem>
            </VueDraggableNext>
            <TagsInputInput placeholder="新增選項" />
          </TagsInput>
        </div>

        <!-- 刪除規格按鈕 -->
        <div class="w-8 mt-6">
          <Button
            v-if="index > 0"
            variant="ghost"
            size="icon"
            class="text-gray-400 hover:text-red-500"
            @click="types.splice(index, 1)"
          >
            <TrashIcon class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </VueDraggableNext>

  <!-- 添加更多規格按鈕 -->
  <Button
    v-if="types.length < 3"
    :disabled="types.length >= 3"
    variant="link"
    class="p-0 text-blue-600 hover:text-blue-700 hover:no-underline"
    @click="types.push({ name: '', values: [] })"
  >
    <PlusIcon class="h-4 w-4" />
    增加規格
  </Button>
</template>