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
import { MapPin, Loader2, X, Pencil } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: null, required: false },
  label: { type: String, default: '' },
  editable: { type: Boolean, default: true },
  deletable: { type: Boolean, default: true },
  hideAction: { type: Boolean, default: false },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const options = ref([])

const fetchData = open => {
  router.reload({
    only: ['locations'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.locations || []
    },
  })
}

const select = option => {
  emit('update:modelValue', Object.assign({}, props.modelValue, option))

  open.value = false
}

watch(open, newValue => {
  if (newValue) fetchData(newValue)
})
</script>

<template>
  <div
    v-if="modelValue"
    class="text-sm space-y-1 flex justify-between w-full"
  >
    <slot name="selected-label">
      <div>
        <div class="text-foreground font-semibold">{{ modelValue?.name }}</div>
        <div>{{ modelValue?.address1 }}</div>
      </div>
    </slot>

    <div
      v-if="!hideAction"
      class="flex gap-2"
    >
      <Pencil
        v-if="editable"
        class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer"
        @click="open = true"
      />

      <X
        v-if="deletable"
        class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer"
        @click="emit('update:modelValue', null)"
      />
    </div>
  </div>

  <Dialog
    v-model:open="open"
    @update:open="fetchData"
  >
    <DialogTrigger as-child>
      <slot v-if="!modelValue" />
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ label }}</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <div class="space-y-ss4 h-[400px] max-h-[400px] overflow-y-auto border-t border-solid">
        <WhenVisible data="locations">
          <template #fallback>
            <Loader2 class="mx-auto my-4 w-4 h-4 animate-spin" />
          </template>

          <div
            v-for="location in options"
            :key="location.id"
            class="p-4 hover:bg-muted cursor-pointer border-b border-solid"
            @click="select(location)"
          >
            <div class="flex items-center gap-2">
              <MapPin class="w-6 h-6 text-muted-foreground" />
              <div class="flex flex-col">
                <span class="text-sm leading-none">{{ location.name }}</span>
                <span class="text-sm text-muted-foreground leading-none">
                  {{ location.address1 }}
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
