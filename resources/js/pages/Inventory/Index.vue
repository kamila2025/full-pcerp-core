<script setup>
import { debounce } from 'lodash'
import { ref, computed, watch, h } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectTrigger, SelectContent, SelectItem, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { SmartTable } from '@/components/app/table'
import { Pencil, Check, X, Ellipsis } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

// ------------------------------------------------
const props = defineProps({
  variants: Object,
  locations: Array,
  //
  params: Object,
  errors: Object,
  auth: Object,
  version: String,
})

// Column
const tableColumns = computed(() => [
  {
    accessorKey: 'product_name',
    header: '商品名稱',
  },
  {
    accessorKey: 'sku',
    header: '商品編號 (SKU)',
  },
  ...props.locations.filter(location => !props.params?.['location_id'] || props.params?.['location_id']?.split(',').includes(String(location.id))).map(location => ({
    accessorKey: `inventories_${location.id}`,
    header: location.name,
    class: 'text-center',
    cell: ({ row }) => {
      const inventory = row.original.inventories.find(inv => inv.location_id === location.id)

      const quantity = inventory?.quantity || 0

      if (row.original.editing) {
        return h(Input, {
          type: 'number',
          modelValue: quantity,
          'onUpdate:modelValue': (value) => {
            if (!inventory) {
              row.original.inventories.push({
                location_id: location.id,
                quantity: value
              })
            } else {
              inventory.quantity = value
            }
          },
          class: 'w-16 text-center justify-self-center',
        })
      }

      // 檢查是否符合篩選條件
      const isHighlighted = () => {
        const min = Number(props.params.inventory_quantity_min)
        const max = Number(props.params.inventory_quantity_max)

        if (!isNaN(min) && !isNaN(max)) {
          return quantity > min && quantity < max
        } else if (!isNaN(min)) {
          return quantity > min
        } else if (!isNaN(max)) {
          return quantity < max
        }
        return false
      }

      return h('div', {
        class: isHighlighted() ? 'font-medium bg-yellow-100 text-yellow-700 px-2 py-1 rounded' : ''
      }, quantity)
    },
  })),
  {
    accessorKey: 'action',
    header: '修改庫存',
    class: 'text-nowrap w-w0 text-right',
  },
])

// FilterPanel
const refFilter = ref(null)

// Filter
const filterFields = [
  {
    key: 'inventory_quantity_min',
    label: '庫存數量多於',
    type: 'number',
    queryMode: 'query',
    defaultValue: '',
  },
  {
    key: 'inventory_quantity_max',
    label: '庫存數量少於',
    type: 'number',
    queryMode: 'query',
    defaultValue: '',
  },
  {
    key: 'location_id',
    label: '地點',
    type: 'multi-select',
    queryMode: 'query',
    defaultValue: [],
    options: props.locations.map(location => ({
      value: String(location.id),
      label: location.name
    }))
  },
]

// 初始化 filters
const filters = ref(JSON.parse(JSON.stringify({
  'inventory_quantity_min': props.params?.['inventory_quantity_min'] || filterFields.find(field => field.key === 'inventory_quantity_min')?.defaultValue,
  'inventory_quantity_max': props.params?.['inventory_quantity_max'] || filterFields.find(field => field.key === 'inventory_quantity_max')?.defaultValue,
  'location_id': props.params?.['location_id']?.split(',') || filterFields.find(field => field.key === 'location_id')?.defaultValue,
})))

const saveItem = row => {
  router.put(`/inventories/${row.id}`, {
    inventories: row.inventories,
  })
}

const clearItem = row => {
  row.editing = false

  row.inventories = JSON.parse(JSON.stringify(props.variants.data.find(v => v.id === row.id).inventories))
}

const refFileInput = ref(null)

const form = useForm({ file: null })

// 匯入 Excel
const handleFileUpload = (event) => {
  form.file = event.target.files[0]
  importExcel()
}

const importExcel = async () => {
  if (!form.file) {
    alert('請選擇 Excel 檔案')
    return
  }

  form.post('/inventories/import')
}

// 匯出 Excel
const exportExcel = () => {
  window.location.href = '/inventories/export'
}

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.variants.path, { ...finalQuery, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 500)

// 監聽 filters 變化
watch(filters, () => fetchData(1), { deep: true })
</script>

<template>
  <AppHeader
    tag="portal"
    :title="`商品庫存數量`"
  >
    <template #actions>
      <!-- <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            size="icon"
          >
            <Ellipsis class="w-5 h-5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem @click="exportExcel">匯出 Excel</DropdownMenuItem>
          <DropdownMenuItem @click="refFileInput.click()">匯入 Excel</DropdownMenuItem>
          <input
            ref="refFileInput"
            type="file"
            accept=".xlsx, .xls"
            class="hidden"
            @change="handleFileUpload"
          />
        </DropdownMenuContent>
      </DropdownMenu> -->
    </template>
  </AppHeader>

  <!-- <div class="flex justify-end items-center">
    <div>
      <Select
        :modelValue="Number(locationId)"
        @update:modelValue="router.get('/inventories', { location_id: $event })"
      >
        <SelectTrigger>
          <SelectValue placeholder="Select" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem
            v-for="location in locations"
            :key="location.id"
            :value="location.id"
          >
            {{ location.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  </div> -->

  <FilterPanel
    ref="refFilter"
    v-model="filters"
    :fields="JSON.parse(JSON.stringify(filterFields))"
  />

  <SmartTable
    :columns="tableColumns"
    :rows="JSON.parse(JSON.stringify(variants.data))"
    :per-page="variants.per_page"
    :page="variants.current_page"
    :total="variants.total"
    @on-page-change="page => fetchData(page)"
  >
    <template #cell(product_name)="data">
      <TextLink
        :href="`/products/${data.row.product.id}/edit`"
        class="text-blue-600 no-underline"
      >
        {{ data.row.product.name }}
      </TextLink>
      <div class="text-sm text-gray-500">
        {{ data.row.name }}
      </div>
    </template>

    <template #cell(action)="data">
      <Button
        v-if="!data.row.editing"
        variant="ghost"
        size="icon"
        @click="data.row.editing = true"
      >
        <Pencil class="w-4 h-4 text-green-500" />
      </Button>

      <Button
        v-if="data.row.editing"
        variant="ghost"
        size="icon"
        @click="saveItem(data.row)"
      >
        <Check class="w-4 h-4 text-green-500" />
      </Button>

      <Button
        v-if="data.row.editing"
        variant="ghost"
        size="icon"
        @click="clearItem(data.row)"
      >
        <X class="w-4 h-4 text-red-500" />
      </Button>
    </template>
  </SmartTable>
</template>
