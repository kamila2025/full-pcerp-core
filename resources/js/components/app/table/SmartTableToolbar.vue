<script setup>
import { computed } from 'vue'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import Cross2Icon from '~icons/radix-icons/cross-2'
import MixerHorizontalIcon from '~icons/radix-icons/mixer-horizontal'

const props = defineProps({
  table: { type: Object, required: false },
})

const isFiltered = computed(() => props.table.getState().columnFilters.length > 0)

const columns = computed(() => props.table
  .getAllColumns()
  .filter(column => typeof column.accessorFn !== 'undefined' && column.getCanHide())
)
</script>

<template>
  <div class="flex items-center justify-between">
    <div class="flex flex-1 items-center space-x-2">
      <Input
        placeholder="Filter users..."
        :model-value="table.getColumn('title')?.getFilterValue() ?? ''"
        class="h-8 w-[150px] lg:w-[250px]"
        @input="table.getColumn('title')?.setFilterValue($event.target.value)"
      />

      <slot name="filter" />

      <Button
        v-if="isFiltered"
        variant="ghost"
        class="h-8 px-2 lg:px-3"
        @click="table.resetColumnFilters()"
      >
        Reset
        <Cross2Icon class="ml-2 h-4 w-4" />
      </Button>
    </div>

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          variant="outline"
          size="sm"
          class="ml-auto hidden h-8 lg:flex"
        >
          <MixerHorizontalIcon class="mr-2 h-4 w-4" />
          View
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        class="w-[150px]"
      >
        <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
        <DropdownMenuSeparator />

        <DropdownMenuCheckboxItem
          v-for="column in columns"
          :key="column.id"
          class="capitalize"
          :model-value="column.getIsVisible()"
          @update:model-value="(value) => column.toggleVisibility(!!value)"
        >
          {{ column.id }}
        </DropdownMenuCheckboxItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
