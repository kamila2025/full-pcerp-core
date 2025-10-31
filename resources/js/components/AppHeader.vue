<script setup>
import { Button } from '@/components/ui/button'
import { ChevronLeftIcon } from 'lucide-vue-next'

const props = defineProps({
  tag: { type: String, default: 'div' },
  title: String,
  isBack: Boolean,
})

const emit = defineEmits({
  backAction: null,
})
</script>

<template>
  <component
    :is="tag"
    :to="tag === 'portal' ? 'pageTitle' : undefined"
    class="flex items-center justify-between"
  >
    <div class="flex items-center flex-1">
      <Button
        v-if="isBack"
        variant="ghost"
        size="icon"
        class="w-6 mr-1"
        @click="emit('backAction')"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </Button>
      <h2 class="text-lg font-semibold truncate flex-auto w-[7rem]">
        {{ title }}
      </h2>
    </div>

    <div class="flex items-center gap-x-1 ml-2">
      <slot name="actions" />
    </div>
  </component>
</template>