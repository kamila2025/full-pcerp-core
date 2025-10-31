<script setup>
import { debounce } from 'lodash'
import { ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { SmartTable } from '@/components/app/table'
import { X, Plus, Ellipsis, History } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import TextLink from '@/components/TextLink.vue'
import FilterPanel from '@/components/FilterPanel.vue'
import moment from 'moment'

const props = defineProps({
  products: Object,
  categories: Object,
  brands: Object,
  //
  params: Object,
  errors: Object,
  auth: Object,
  version: String,
})

// Column
const tableColumns = [
  {
    accessorKey: 'name',
    header: '商品名稱',
  },
  {
    accessorKey: 'stock',
    header: '庫存數量',
  },
  {
    accessorKey: 'status',
    header: '上架狀態',
  },
  {
    accessorKey: 'price',
    header: '價格',
    class: 'text-center w-0 whitespace-nowrap',
  },
  {
    accessorKey: 'action',
    header: '',
    class: 'text-right w-0 whitespace-nowrap',
  },
]

const onDeleting = row => {
  if (confirm('確定要刪除嗎？')) {
    router.delete(`${props.products.path}/${row.id}`)
  }
}

const onOrdered = row => {
  router.post(`${props.products.path}/positions`, { positions: row }, {
    prefetch: true,
    onPrefetched: () => router.reload(),
  })
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

  form.post('/products/import')
}

// 匯出 Excel
const exportExcel = () => {
  window.location.href = '/products/export'
}

// FilterPanel
const refFilter = ref(null)

// Filter
const filterFields = [
  {
    key: 'search_field',
    type: 'search-combo',
    label: '搜尋',
    queryMode: 'search',
    defaultValue: 'name',
    options: [
      { value: 'name', label: '商品名稱' },
    ]
  },
  {
    key: 'categories.category_id',
    label: '分類',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.categories).map(([value, label]) => ({
      value,
      label
    }))
  },
  {
    key: 'brands.tag_id',
    label: '品牌',
    type: 'multi-select',
    queryMode: 'search',
    defaultValue: [],
    options: Object.entries(props.brands).map(([value, label]) => ({
      value,
      label
    }))
  },
]

// 初始化 filters
const filters = ref({
  'search_field': props.params?.['search_field'] || filterFields.find(field => field.key === 'search_field')?.defaultValue,
  'name': props.params?.['name'] || filterFields.find(field => field.key === 'name')?.defaultValue,
  'categories.category_id': props.params?.['categories.category_id']?.split(',') || filterFields.find(field => field.key === 'categories.category_id')?.defaultValue,
  'brands.tag_id': props.params?.['brands.tag_id']?.split(',') || filterFields.find(field => field.key === 'brands.tag_id')?.defaultValue,
})

// 抓取資料
const fetchData = debounce((page = 1) => {
  const finalQuery = refFilter.value?.parseFilters(filters.value, JSON.parse(JSON.stringify(filterFields)))

  router.get(props.products.path, { ...finalQuery, page }, {
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
    :title="`所有商品`"
  >
    <template #actions>
      <DropdownMenu>
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
      </DropdownMenu>

      <Button
        variant="outline"
        size="icon"
        @click="router.get(`${products.path}/create`)"
      >
        <Plus />
      </Button>
    </template>
  </AppHeader>

  <FilterPanel
    ref="refFilter"
    v-model="filters"
    :fields="JSON.parse(JSON.stringify(filterFields))"
  />

  <SmartTable
    reorder
    reorder-class="hidden sm:table-cell"
    :columns="tableColumns"
    :rows="products.data"
    :per-page="products.per_page"
    :page="products.current_page"
    :total="products.total"
    @on-page-change="page => fetchData(page)"
    @on-order-change="onOrdered"
  >
    <template #cell(name)="data">
      <div class="flex flex-col">
        <TextLink
          :href="`${products.path}/${data.row.id}/edit`"
          class="text-blue-600 no-underline"
        >
          {{ data.value }}
        </TextLink>
        <small class="text-muted-foreground text-xs">{{ moment(data.row.updated_at).fromNow(true) }}前更新</small>
      </div>
    </template>

    <template #cell(stock)="data">
      <div v-if="data.row.inventory_management === 'none'">∞</div>
      <div
        v-else
        :class="{ 'text-red-500': data.row.inventories_sum_quantity < 0 }"
      >
        <span v-if="data.row.variants_count > 1"> {{ data.row.variants_count }} 規格總和 </span>
        <span> {{ data.row.inventories_sum_quantity || 0 }} </span>
      </div>
    </template>

    <template #cell(status)="data">
      <Badge v-if="data.row.status === 'published'">
        上架
      </Badge>
      <Badge
        v-else-if="data.row.status === 'unpublished'"
        variant="secondary"
      >
        下架
      </Badge>
      <Badge
        v-else
        variant="outline"
      >
        {{ data.row.status }}
      </Badge>
    </template>

    <template #cell(price)="data">
      <span v-if="!Boolean(data.row.variants_min_price) || !Boolean(data.row.variants_max_price)">-</span>
      <span v-else-if="data.row.variants_min_price === data.row.variants_max_price">{{ Number(data.row.variants_min_price).toLocaleString() }} TWD</span>
      <span v-else>{{ Number(data.row.variants_min_price).toLocaleString() }} ~ {{ Number(data.row.variants_max_price).toLocaleString() }} TWD</span>
    </template>

    <template #cell(action)="data">
      <Button
        v-if="data.row.inventory_management === 'store'"
        variant="ghost"
        size="icon"
        class="text text-right"
        @click="router.get(`/inventories/logs`, { product_id: data.row.id, back_url: '/products' })"
      >
        <History class="w-4 h-4 text-blue-500" />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        class="text text-right"
        @click="onDeleting(data.row)"
      >
        <X class="w-4 h-4 text-red-500" />
      </Button>
    </template>
  </SmartTable>
</template>
