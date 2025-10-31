<script setup>
import { ref, watch } from 'vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { PackageIcon } from 'lucide-vue-next'
import VariantInventoryTable from './VariantInventoryTable.vue'

const props = defineProps({
  modelValue: {
    type: Array,
    required: true,
  },
  types: {
    type: Array,
    required: true,
  },
  locations: {
    type: Array,
    required: true,
  },
  inventoryManagement: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue'])

// 保存變體數據的響應式數組
const variants = ref([])

// 庫存管理彈窗
const showInventoryDialog = ref(false)

// 選中的變體索引
const selectedVariantIndex = ref(null)

// 打開庫存管理彈窗
const openInventoryDialog = variantIndex => {
  showInventoryDialog.value = true

  selectedVariantIndex.value = variantIndex
}

// 更新庫存數量
const updateInventory = event => {
  if (event.variantIndex === null || event.variantIndex === undefined || !event.locationId) return

  const variant = variants.value.find((v, index) => index === (selectedVariantIndex.value || event.variantIndex))

  const inventory = variant.inventories.find(inv => inv.location_id === event.locationId)

  if (inventory) inventory.quantity = parseInt(event.quantity) || 0
}

// 獲取所有變體數據
const getVariantsData = () => variants.value

// 默認變體對象
const createDefaultVariant = (values = []) => ({
  values,
  price: 0,
  compare_at_price: 0,
  cost_price: 0,
  sku: '',
  barcode: '',
  status: 'enabled',
  inventories: props.locations.map(location => ({
    location_id: location.id,
    quantity: 0
  }))
})

// 生成所有可能的規格組合
const generateVariantCombinations = (types) => {
  // 檢查是否有效的類型
  const validTypes = types.filter(type => type.name?.trim() && Array.isArray(type.values) && type.values.length > 0)

  if (validTypes.length === 0) {
    return []
  }

  const generateCombinations = (index, current) => {
    if (index === validTypes.length) {
      return [current]
    }

    const combinations = []

    const currentType = validTypes[index]

    // 過濾有效的選項
    const validValues = currentType.values.filter(option => option.name?.trim())

    for (const option of validValues) {
      const newCombination = [...current, {
        type_id: currentType.id,
        type_name: currentType.name,
        option_id: option.id,
        name: option.name
      }]
      combinations.push(...generateCombinations(index + 1, newCombination))
    }

    return combinations
  }

  return generateCombinations(0, [])
}

// 監聽變體數據變化
watch(variants, () => {
  emit('update:modelValue', getVariantsData())
}, { deep: true })

// 監聽 types 變化
watch(() => props.types, () => {
  if (props.types.length === 0) {
    variants.value = [createDefaultVariant()]
    return
  }

  // 生成新的規格組合
  const combinations = generateVariantCombinations(props.types)

  // 保留已存在變體的數據
  const newVariants = combinations.map(combination => {
    // 嘗試找到匹配的現有變體
    const existingVariant = props.modelValue.find(v => {
      if (v.values.length !== combination.length) return false

      // 建立一個 Set 來存儲所有選項名稱的組合
      const combinationSet = new Set(combination.map(c => c.name))
      const variantSet = new Set(v.values.map(opt => opt.name))

      // 比較兩個 Set 是否完全相同
      return (
        combinationSet.size === variantSet.size &&
        [...combinationSet].every(name => variantSet.has(name))
      )
    })

    // 如果找到匹配的變體，保留其數據
    if (existingVariant) {
      return {
        ...existingVariant,
        values: combination
      }
    }

    // 否則創建新的默認變體
    return createDefaultVariant(combination)
  })

  variants.value = newVariants
}, { deep: true, immediate: true })
</script>

<template>
  <!-- 規格組合表格 -->
  <Table class="mt-">
    <TableHeader>
      <TableRow>
        <TableHead class="text-nowrap">規格</TableHead>
        <TableHead
          v-if="inventoryManagement === 'store'"
          class="flex items-center gap-1"
        >
          <span>庫存</span>
          <Button
            variant="ghost"
            size="icon"
            class="h-4 w-4 text-blue-500 hover:text-blue-600"
            @click="openInventoryDialog()"
          >
            <PackageIcon class="h-4 w-4" />
          </Button>
        </TableHead>
        <TableHead class="text-nowrap">編號</TableHead>
        <TableHead>售價 (TWD)<span class="text-xs text-red-500 ml-1">*</span></TableHead>
        <TableHead>原價 (TWD)</TableHead>
        <TableHead>成本價 (TWD)</TableHead>
        <TableHead>條碼</TableHead>
        <!-- <TableHead>已啟用</TableHead> -->
      </TableRow>
    </TableHeader>

    <TableBody>
      <TableRow
        v-for="(variant, variantIndex) in variants"
        :key="`variant-${variantIndex}-${variant.values.map(item => item.name).join(' . ')}`"
      >
        <TableCell>
          <span class="text-nowrap">
            <template
              v-for="(item, index) in variant.values"
              :key="index"
            >
              <span :class="{ 'text-orange-600': index === 0, 'text-emerald-600': index === 1, 'text-purple-600': index === 2 }">
                {{ item.name }}
              </span>
              <span
                v-if="index < variant.values.length - 1"
                class="text-gray-400"
              > . </span>
            </template>
          </span>
        </TableCell>
        <TableCell v-if="inventoryManagement === 'store'">
          <div class="flex items-center gap-1 text-nowrap">
            <span>總和 {{ variant?.inventories?.reduce((total, inv) => total + inv.quantity, 0) ?? 0 }}</span>
            <Button
              variant="ghost"
              size="icon"
              class="h-4 w-4 text-blue-500 hover:text-blue-600"
              @click="openInventoryDialog(variantIndex)"
            >
              <PackageIcon class="h-4 w-4" />
            </Button>
          </div>
        </TableCell>
        <TableCell>
          <Input
            v-model="variant.sku"
            class="w-20"
          />
        </TableCell>
        <TableCell>
          <Input
            v-model="variant.price"
            type="number"
            class="w-24"
            :class="{ 'border-red-500': variant.price === null || variant.price === '' }"
          />
        </TableCell>
        <TableCell>
          <Input
            v-model="variant.compare_at_price"
            type="number"
            class="w-24"
            :class="{ 'border-red-500': variant.compare_at_price && variant.compare_at_price < variant.price }"
          />
        </TableCell>
        <TableCell>
          <Input
            v-model="variant.cost_price"
            type="number"
            class="w-24"
          />
        </TableCell>
        <TableCell>
          <Input
            v-model="variant.barcode"
            class="w-24"
          />
        </TableCell>
        <!-- <TableCell>
          <Switch
            :model-value="variant.status === 'enabled'"
            @update:model-value="variant.status = $event ? 'enabled' : 'disabled'"
          />
        </TableCell> -->
      </TableRow>
    </TableBody>
  </Table>

  <!-- 庫存管理彈窗 -->
  <Dialog v-model:open="showInventoryDialog">
    <DialogContent class="sm:max-w-[800px]">
      <DialogHeader>
        <DialogTitle>庫存管理</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <VariantInventoryTable
        :model-value="selectedVariantIndex == null ? variants : variants.filter((v, index) => index === selectedVariantIndex)"
        :locations="locations"
        @update-inventory="updateInventory"
      />
    </DialogContent>
  </Dialog>
</template>