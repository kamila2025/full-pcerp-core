<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, WhenVisible } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Loader2, X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: null, required: false },
  label: { type: String, default: 'Select' },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const options = ref([])

const fetchData = open => {
  router.reload({
    only: ['users'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.users || []
    },
  })
}

const select = option => {
  emit('update:modelValue', option)

  open.value = false
}
</script>

<template>
  <div
    v-if="modelValue"
    class="text-sm space-y-1"
  >
    <div class="flex items-center justify-between">
      <h2 class="font-semibold">銷售員</h2>
      <X
        class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer"
        @click="emit('update:modelValue', null)"
      />
    </div>

    <div>{{ modelValue.name }}</div>
  </div>

  <Dialog
    v-else
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

      <div class="space-y-ss4 h-[400px] max-h-[400px] overflow-y-auto border-t border-solid">
        <WhenVisible data="users">
          <template #fallback>
            <Loader2 class="mx-auto my-4 w-4 h-4 animate-spin" />
          </template>

          <div
            v-for="option in options"
            :key="option.id"
            class="p-4 hover:bg-muted cursor-pointer border-b border-solid"
            @click="select(option)"
          >
            <div class="flex items-center gap-2">
              <Avatar class="w-10 h-10 rounded-full font-medium text-sm">
                <AvatarFallback>{{ option.name.split(' ').map((w) => w[0]).join('').toUpperCase() }}</AvatarFallback>
              </Avatar>
              <div class="flex flex-col">
                <span class="text-sm leading-none">{{ option.name }}</span>
                <span class="text-sm text-muted-foreground leading-none">
                  {{ option.email }}
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="options?.length === 0"
            class="my-3 text-center text-muted-foreground"
          >
            No results found
          </div>
        </WhenVisible>
      </div>
    </DialogContent>
  </Dialog>
</template>
