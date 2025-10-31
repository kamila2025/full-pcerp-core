<script setup>
import { ref, computed, watch } from 'vue'
import { valueUpdater } from '@/lib/utils'
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  FlexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Pagination,
  PaginationEllipsis,
  PaginationFirst,
  PaginationLast,
  PaginationList,
  PaginationListItem,
  PaginationNext,
  PaginationPrev,
} from '@/components/ui/pagination'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { VueDraggableNext } from 'vue-draggable-next'
import { GripVertical } from 'lucide-vue-next'
import ArrowDownIcon from '~icons/radix-icons/arrow-down'
import ArrowUpIcon from '~icons/radix-icons/arrow-up'
import CaretSortIcon from '~icons/radix-icons/caret-sort'

const props = defineProps({
  tableClass: String,
  reorderClass: String,
  theadClass: String,
  rowClass: String,
  columns: Array,
  rows: Array,
  page: Number,
  total: Number,
  perPage: Number,
  //
  reorder: Boolean,
  selectable: Boolean,
  pagination: { type: Boolean, default: true }, // 預設開啟分頁功能
})

const emit = defineEmits({
  onSortingChange: null,
  onPageChange: null,
  onOrderChange: null,
})

const enableRowsDrag = ref(props.reorder)
const enableRowsSelection = ref(props.selectable)
const sorting = ref([])
const columnFilters = ref([])
const columnVisibility = ref({})
const rowSelection = ref({})
const localRows = ref(props.rows)

watch(() => props.rows, (newVal, oldVal) => {
  localRows.value = newVal
})

const table = useVueTable({
  get columns() { return props.columns.map(column => ({ ...column, enableSorting: Boolean(column.enableSorting) })) },
  get data() { return localRows.value },
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
  },
  enableRowSelection: true,
  onSortingChange: updaterOrValue => {
    valueUpdater(updaterOrValue, sorting)

    emit('onSortingChange', sorting.value)
  },
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
  getCoreRowModel: getCoreRowModel(),
  // getFilteredRowModel: getFilteredRowModel(),
  // getPaginationRowModel: getPaginationRowModel(),
  // getSortedRowModel: getSortedRowModel(),
  // getFacetedRowModel: getFacetedRowModel(),
  // getFacetedUniqueValues: getFacetedUniqueValues(),
})

// 排序比較函式（外部獨立）
const transformPositions = (original, sorted) => {
  return sorted.map((item, index) => {
    const originalPosition = original.find(o => o.id === item.id)?.position

    return {
      id: item.id,
      name: item.name,
      from_position: originalPosition,
      to_position: index + 1,
    }
  })
}

// 處理拖曳完成
const handleDragEnd = evt => {
  emit('onOrderChange', transformPositions(props.rows, localRows.value))
}
</script>

<template>
  <slot
    name="toolbar"
    v-bind="{ table: table }"
  />

  <Table :class="tableClass">
    <TableHeader :class="theadClass">
      <TableRow
        v-for="headerGroup in table.getHeaderGroups()"
        :key="headerGroup.id"
      >
        <TableHead
          v-if="enableRowsDrag || enableRowsSelection"
          :class="reorderClass"
        >
          <Checkbox
            v-if="enableRowsSelection"
            :model-value="table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')"
            :class="{ 'ml-6': enableRowsDrag }"
            class="translate-y-0.5"
            @update:modelValue="value => table.toggleAllPageRowsSelected(!!value)"
          />
        </TableHead>

        <TableHead
          v-for="header in headerGroup.headers"
          :key="header.id"
          :class="header.column.columnDef.class"
        >
          <div
            v-if="header.column.getCanSort()"
            class="flex items-center space-x-2"
          >
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="ghost"
                  size="sm"
                  class="-ml-3 h-8 data-[state=open]:bg-accent"
                >
                  <span>{{ header.column.columnDef.header }}</span>
                  <ArrowDownIcon
                    v-if="header.column.getIsSorted() === 'desc'"
                    class="ml-2 h-4 w-4"
                  />
                  <ArrowUpIcon
                    v-else-if="header.column.getIsSorted() === 'asc'"
                    class="ml-2 h-4 w-4"
                  />
                  <CaretSortIcon
                    v-else
                    class="ml-2 h-4 w-4"
                  />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start">
                <DropdownMenuItem @click="header.column.toggleSorting(false)">
                  <ArrowUpIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                  Asc
                </DropdownMenuItem>
                <DropdownMenuItem @click="header.column.toggleSorting(true)">
                  <ArrowDownIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                  Desc
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="header.column.clearSorting()">
                  <CaretSortIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                  Default
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <FlexRender
            v-else-if="!header.isPlaceholder"
            :render="header.column.columnDef.header"
            :props="header.getContext()"
          />
          <!-- <span v-else>{{ header.column.columnDef.header }}</span> -->
        </TableHead>
      </TableRow>
    </TableHeader>

    <component
      v-if="table.getRowModel().rows.length"
      :is="enableRowsDrag ? VueDraggableNext : TableBody"
      v-model="localRows"
      tag="tbody"
      handle=".drag-handle"
      @end="handleDragEnd"
    >
      <TableRow
        v-for="(row, rowIndex) in table.getRowModel().rows"
        :key="`body-row-${rowIndex}`"
        :data-state="row.getIsSelected() ? 'selected' : null"
        :class="rowClass?.(row)"
      >
        <TableCell
          v-if="enableRowsDrag || enableRowsSelection"
          :class="['relative w-10', reorderClass]"
        >
          <GripVertical
            v-if="enableRowsDrag"
            class="drag-handle w-4 h-full absolute cursor-pointer opacity-50 top-0 bottom-0"
          />

          <Checkbox
            v-if="enableRowsSelection"
            :model-value="row.getIsSelected()"
            :class="{ 'ml-6': enableRowsDrag }"
            class="translate-y-0.5"
            @update:modelValue="value => row.toggleSelected(!!value)"
          />
        </TableCell>

        <TableCell
          v-for="(cell, cellIndex) in row.getVisibleCells()"
          :key="`body-cell-${cellIndex}`"
          :class="cell.column.columnDef.class"
        >
          <slot
            :name="`cell(${cell.column.id})`"
            v-bind="{ row: row.original, value: cell.getValue() }"
          >
            <FlexRender
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </slot>
        </TableCell>
      </TableRow>
    </component>

    <TableBody v-else>
      <TableRow>
        <TableCell
          :colspan="columns.length"
          class="h-24 text-center"
        >
          No results.
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>

  <Pagination
    v-if="pagination"
    v-slot="{ page }"
    :items-per-page="perPage"
    :page="page"
    show-edges
    :sibling-count="1"
    :total="total"
    class="flex justify-end"
    @update:page="page => emit('onPageChange', page)"
  >
    <PaginationList
      v-slot="{ items }"
      class="flex items-center gap-1"
    >
      <PaginationPrev class="h-8 w-8 p-0" />

      <template v-for="(item, index) in items">
        <PaginationListItem
          v-if="item.type === 'page'"
          :key="index"
          :value="item.value"
          as-child
        >
          <Button
            class="h-8 w-8 p-0"
            :variant="item.value === page ? 'default' : 'outline'"
          >
            {{ item.value }}
          </Button>
        </PaginationListItem>
        <PaginationEllipsis
          v-else
          :key="item.type"
          :index="index"
        />
      </template>

      <PaginationNext class="h-8 w-8 p-0" />
    </PaginationList>
  </Pagination>
</template>
