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
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Loader2, X, Search } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: null, required: false },
  label: { type: String, default: 'Select' },
  customerId: { type: [String, Number], default: null },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const options = ref([])

const search = ref('')

// 新增 computed 屬性來優化搜尋效能
const filteredOptions = computed(() => {
  if (!search.value.trim()) {
    return options.value
  }

  const searchTerm = search.value.toLowerCase().trim()

  return options.value.filter(option => {
    // 搜尋姓名
    if (option.name && option.name.toLowerCase().includes(searchTerm)) {
      return true
    }

    // 搜尋電子郵件
    if (option.email && option.email.toLowerCase().includes(searchTerm)) {
      return true
    }

    // 搜尋電話號碼
    if (option.phone && option.phone.toLowerCase().includes(searchTerm)) {
      return true
    }

    // 搜尋客戶 ID
    if (option.id && option.id.toString().includes(searchTerm)) {
      return true
    }

    return false
  })
})

const fetchData = open => {
  search.value = ''

  router.reload({
    only: ['customers'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.customers || []
    },
  })
}

if (props.customerId) {
  router.reload({
    only: ['customers'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.customers || []

      emit('update:modelValue', options.value.find(option => option.id === Number(props.customerId)))
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
    class="text-sm w-full"
  >
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Avatar class="w-10 h-10 rounded-full font-medium text-sm">
          <AvatarFallback>{{ modelValue.name.split(' ').map((w) => w[0]).join('').toUpperCase() }}</AvatarFallback>
        </Avatar>

        <div class="flex flex-col">
          <span class="text-primary font-medium leading-tight">{{ modelValue.name }}</span>
          <span class="text-sm text-muted-foreground">{{ modelValue.email|| modelValue.phone }}</span>
        </div>
      </div>
      <X
        class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer"
        @click="emit('update:modelValue', null)"
      />
    </div>
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

      <div class="relative w-full items-center">
        <Input
          v-model="search"
          type="text"
          placeholder="Search..."
          class="pl-10"
        />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
          <Search class="size-6 text-muted-foreground" />
        </span>
      </div>

      <div class="h-[400px] max-h-[400px] overflow-y-auto border-t border-solid">
        <WhenVisible data="users">
          <template #fallback>
            <Loader2 class="mx-auto my-4 w-4 h-4 animate-spin" />
          </template>

          <div
            v-for="option in filteredOptions"
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
                  {{ option.email || option.phone }}
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="filteredOptions.length === 0"
            class="my-3 text-center text-muted-foreground"
          >
            No results found
          </div>
        </WhenVisible>
      </div>
    </DialogContent>
  </Dialog>
</template>
