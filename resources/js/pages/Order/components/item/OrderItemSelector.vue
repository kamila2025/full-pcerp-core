<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { router, WhenVisible } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
  CommandShortcut,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Loader2, ChevronsUpDown, Search, Check, RefreshCw } from 'lucide-vue-next'
import AddCustomItemDialog from './AddCustomItemDialog.vue'

const props = defineProps({
  modelValue: { type: null, required: false },
  groupBy: { type: String, required: false },
  category: { type: String, required: false },
  selected: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'update:modelValue',
  'select',
])

// 本地 shadow 資料
const isLoading = ref(false)

const showWhenVisible = ref(true)

const isOpen = ref(false)

const showAddDialog = ref(false)

const options = ref([])

// 動態分組邏輯
const groupedOptions = computed(() => {
  if (!props.groupBy) return [[null, options.value]]

  const groupPath = props.groupBy.split('.') // e.g. ['categories', 'name']

  const groups = new Map()

  options.value.forEach(item => {
    let value = item

    for (const key of groupPath) {
      // 支援 array 結構 e.g. categories[0].name
      if (Array.isArray(value)) {
        value = value[0] || null
      }
      value = value?.[key]
      if (!value) break
    }

    const groupKey = value || null
    if (!groups.has(groupKey)) groups.set(groupKey, [])
    groups.get(groupKey).push(item)
  })

  return Array.from(groups.entries())
    .sort(([groupA], [groupB]) => {
      if (groupA === null || groupA === '') return 1
      if (groupB === null || groupB === '') return -1
      return 0
    })
})

const onSelect = value => {
  isOpen.value = false

  emit('update:modelValue', value)

  emit('select', value)
}

const onSubmit = value => {
  isOpen.value = false

  emit('update:modelValue', value)

  emit('select', value)
}

const fetchProducts = () => {
  isLoading.value = true

  router.reload({
    data: { category_id: props.category },
    only: ['variants'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      options.value = page.props?.variants || []

      isLoading.value = false
    },
  })
}

// Dialog 開啟時自動 reload
watch(isOpen, val => {
  if (val) fetchProducts()
})
</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        class="relative w-full h-8 px-2 md:pl-7 pr-7 overflow-hidden"
        :class="cn('justify-between', !modelValue && 'text-muted-foreground ')"
      >
        <Search class="absolute left-2 size-4 text-muted-foreground hidden md:block" />
        <span :class="[{ 'truncate': modelValue }]">{{ modelValue?.product_name || modelValue?.product?.name || 'Select' }}<small class="text-muted-foreground ml-1">{{ modelValue?.name }}</small></span>
        <ChevronsUpDown class="absolute right-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>

    <PopoverContent class="p-0 w-80">
      <Command class="w-full">
        <CommandInput
          :disabled="isLoading"
          placeholder="Search..."
          class="w-full"
        />
        <CommandList class="h-[300px]">
          <CommandEmpty>No results found.</CommandEmpty>

          <WhenVisible data="variants">
            <template #fallback>
              <Loader2 class="mx-auto my-4 w-4 h-4 animate-spin" />
            </template>

            <template
              v-for="[gorup, options] in groupedOptions"
              :key="gorup"
            >
              <CommandGroup :heading="gorup || 'No Group'">
                <template
                  v-for="(option, optionIndex) in options"
                  :key="`option-${optionIndex}`"
                >
                  <CommandItem
                    :value="option.id"
                    :disabled="selected.includes(option.id)"
                    class="flex justify-between items-center"
                    @select="onSelect(option)"
                  >
                    <div class="flex items-center gap-2">
                      <div class="flex flex-col">
                        <span>{{ option.product?.name }}<small class="text-muted-foreground">&nbsp;{{ option?.name }}</small></span>

                        <span class="text-xs text-muted-foreground">
                          {{ (option.product?.inventory_management === 'store') ? `剩餘庫存 ${(option.inventories_sum_quantity || 0)}` : '無追蹤' }}
                          | 已售出 {{ option.order_items_sum_quantity ?? 0 }}
                        </span>
                      </div>
                      <Check
                        v-if="selected.includes(option.id) || option.id === modelValue?.id"
                        class="ml-auto flex h-5 w-5 text-green-500"
                      />
                    </div>

                    <div class="whitespace-nowrap text-xs flex flex-col items-end">
                      <span>NT$ {{ option.price.toLocaleString() }}</span>
                      <span class="text-muted-foreground">(成本 {{ option.cost_price?.toLocaleString() }})</span>
                    </div>
                  </CommandItem>
                </template>
              </CommandGroup>
              <CommandSeparator />
            </template>

            <div
              v-if="groupedOptions?.length === 0"
              class="my-3 text-center text-muted-foreground"
            >
              No results found
            </div>
          </WhenVisible>
        </CommandList>
      </Command>

      <hr>

      <div class="flex items-center justify-between p-1">
        <div />

        <Button
          variant="outline"
          @click="showAddDialog = true"
        >
          新增商品
        </Button>

        <AddCustomItemDialog
          :open="showAddDialog"
          @update:open="showAddDialog = $event"
          @submit="onSubmit"
        />
      </div>
    </PopoverContent>
  </Popover>
</template>
