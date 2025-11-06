<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Badge } from '@/components/ui/badge'
import { History, ChevronsUpDown, Check, X } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'
import VariantTable from './components/VariantTable.vue'
import VariantType from './components/VariantType.vue'

// ------------------------------------------------
const props = defineProps({
  product: Object,
  locations: Array,
  categories: Object,
  brands: Object,
})

const variant = ref({
  price: 50,
  compare_at_price: 100,
  cost_price: 0,
  status: 'enabled',
  inventories: props.locations.map(location => ({
    location_id: location.id,
    location: location.name,
    quantity: 0,
  })),
})

const variants = ref(props.product?.variants.map(variant => ({
  id: variant.id,
  price: variant.price,
  compare_at_price: variant.compare_at_price,
  cost_price: variant.cost_price,
  status: variant.status ?? 'enabled',
  inventories: props.locations.map(location => ({
    location_id: location.id,
    location: location.name,
    quantity: variant.inventories?.find(inventory => inventory.location_id === location.id)?.quantity || 0,
  })),
  values: variant.values.map(value => ({
    id: value.id,
    name: value.name,
  })),
})))

const types = ref(props.product?.types.map(type => ({
  id: type.id,
  name: type.name,
  values: type.values.map(value => ({
    id: value.id,
    name: value.name,
  })),
})))

const form = useForm({
  name: props.product?.name || '',
  description: props.product?.description || '',
  inventory_management: props.product?.inventory_management || 'none',
  categories: props.product?.categories.map(category => category.name) || [],
  brands: props.product?.brands.map(brand => brand.name_display) || [],
  status: props.product?.status || 'published',
  types: types.value || [],
  variants: variants.value || [variant.value],
})

// 分类相关逻辑
const categoriesOpen = ref(false)
const categorySearch = ref('')

// 将 categories prop 转换为选项数组
const categoryOptions = computed(() => {
  if (!props.categories) return []
  return Object.entries(props.categories).map(([id, name]) => ({
    value: String(id),
    label: String(name),
  }))
})

// 已选择的分类名称
const selectedCategoryNames = computed(() => form.categories || [])

// 过滤后的分类选项（用于搜索）
const filteredCategoryOptions = computed(() => {
  if (!categorySearch.value) return categoryOptions.value
  
  const searchLower = categorySearch.value.toLowerCase()
  return categoryOptions.value.filter(option => 
    option.label.toLowerCase().includes(searchLower)
  )
})

// 检查是否可以创建新分类（搜索内容不在现有分类中）
const canCreateNewCategory = computed(() => {
  if (!categorySearch.value.trim()) return false
  const searchLower = categorySearch.value.trim().toLowerCase()
  return !categoryOptions.value.some(option => 
    option.label.toLowerCase() === searchLower
  ) && !selectedCategoryNames.value.some(name => 
    name.toLowerCase() === searchLower
  )
})

// 选择分类
const selectCategory = (categoryName) => {
  if (!selectedCategoryNames.value.includes(categoryName)) {
    form.categories = [...selectedCategoryNames.value, categoryName]
  }
  categorySearch.value = ''
  categoriesOpen.value = false
}

// 创建新分类
const createNewCategory = () => {
  const newCategoryName = categorySearch.value.trim()
  if (newCategoryName && !selectedCategoryNames.value.includes(newCategoryName)) {
    form.categories = [...selectedCategoryNames.value, newCategoryName]
  }
  categorySearch.value = ''
  categoriesOpen.value = false
}

// 移除分类
const removeCategory = (categoryName) => {
  form.categories = selectedCategoryNames.value.filter(name => name !== categoryName)
}

// 品牌相关逻辑
const brandsOpen = ref(false)
const brandSearch = ref('')

// 将 brands prop 转换为选项数组
const brandOptions = computed(() => {
  if (!props.brands) return []
  return Object.entries(props.brands).map(([id, name]) => ({
    value: String(id),
    label: String(name),
  }))
})

// 已选择的品牌名称
const selectedBrandNames = computed(() => form.brands || [])

// 过滤后的品牌选项（用于搜索）
const filteredBrandOptions = computed(() => {
  if (!brandSearch.value) return brandOptions.value
  
  const searchLower = brandSearch.value.toLowerCase()
  return brandOptions.value.filter(option => 
    option.label.toLowerCase().includes(searchLower)
  )
})

// 检查是否可以创建新品牌（搜索内容不在现有品牌中）
const canCreateNewBrand = computed(() => {
  if (!brandSearch.value.trim()) return false
  const searchLower = brandSearch.value.trim().toLowerCase()
  return !brandOptions.value.some(option => 
    option.label.toLowerCase() === searchLower
  ) && !selectedBrandNames.value.some(name => 
    name.toLowerCase() === searchLower
  )
})

// 选择品牌
const selectBrand = (brandName) => {
  if (!selectedBrandNames.value.includes(brandName)) {
    form.brands = [...selectedBrandNames.value, brandName]
  }
  brandSearch.value = ''
  brandsOpen.value = false
}

// 创建新品牌
const createNewBrand = () => {
  const newBrandName = brandSearch.value.trim()
  if (newBrandName && !selectedBrandNames.value.includes(newBrandName)) {
    form.brands = [...selectedBrandNames.value, newBrandName]
  }
  brandSearch.value = ''
  brandsOpen.value = false
}

// 移除品牌
const removeBrand = (brandName) => {
  form.brands = selectedBrandNames.value.filter(name => name !== brandName)
}

const submit = () => {
  if (props.product) form.put(`/products/${props.product.id}`)

  else form.post('/products')
}
</script>

<template>
  <AppHeader
    tag="portal"
    :title="product ? product.name : `新增商品`"
    is-back
    @backAction="router.get('/products')"
  >
    <template #actions>
      <Button
        v-if="product"
        variant="outline"
        size="icon"
        :disabled="form.processing"
        @click="router.get(`/inventories/logs`, { product_id: product.id })"
      >
        <History class="w-4 h-4" />
      </Button>
      <Button
        variant="secondary"
        @click="submit"
      >
        Save
      </Button>
    </template>
  </AppHeader>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
      <Card>
        <CardContent class="pt-6 space-y-4">
          <div class="grid gap-2">
            <Label for="name">商品名稱</Label>
            <Input
              id="name"
              v-model="form.name"
              autofocus
              autocomplete="name"
              placeholder="為您的商品命名"
              @change="form.clearErrors('name')"
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="grid gap-2">
            <Label for="description">商品描述</Label>
            <Textarea
              id="description"
              v-model="form.description"
              autofocus
              placeholder="請在此輸入您的商品描述內容"
              @change="form.clearErrors('description')"
            />
            <InputError :message="form.errors.description" />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-scenter justify-between">
          <div>商品規格</div>
          <Button
            v-if="form.types.length > 0"
            variant="link"
            class="text-blue-600 hover:text-blue-500 px-0 hover:no-underline"
            @click="{
              form.types = form.types.slice(0, -1);
              form.variants = [variant];
            }"
          >
            刪除
          </Button>
          <Button
            v-else
            variant="link"
            class="text-blue-600 hover:text-blue-500 px-0 hover:no-underline"
            @click="form.types.push({ name: '大小', values: [] })"
          >
            新增商品規格
          </Button>
        </CardHeader>

        <CardContent>
          <template v-if="form.types.length > 0">
            <VariantType v-model="form.types" />

            <VariantTable
              v-model="form.variants"
              :locations="locations"
              :types="form.types"
              :inventory-management="form.inventory_management"
            />
          </template>

          <span
            v-else
            class="text-muted-foreground text-sm"
          >
            為您的商品新增規格，例：尺寸、顏色
          </span>
        </CardContent>
      </Card>

      <Card v-if="form.types.length > 0">
        <CardHeader>
          <CardTitle>其他</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-2">
            <Label for="inventory_management">是否追蹤庫存數量</Label>
            <Select v-model="form.inventory_management">
              <SelectTrigger>
                <SelectValue placeholder="Select" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="store">追蹤庫存數量</SelectItem>
                <SelectItem value="none">不追蹤庫存數量</SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.inventory_management" />
          </div>
        </CardContent>
      </Card>

      <template
        v-if="form.variants.length === 1 && form.types.length === 0"
        v-for="(variant, index) in form.variants"
        :key="index"
      >
        <Card>
          <CardHeader>價格</CardHeader>
          <CardContent class="grid grid-cols md:grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="price">售價</Label>
              <Input
                id="price"
                type="number"
                v-model="variant.price"
                @change="form.clearErrors('price')"
              />
              <InputError :message="form.errors[`variants.${index}.price`]" />
            </div>

            <div class="grid gap-2">
              <Label for="compare_at_price">原價</Label>
              <Input
                id="compare_at_price"
                type="number"
                v-model="variant.compare_at_price"
                @change="form.clearErrors('compare_at_price')"
              />
              <InputError :message="form.errors[`variants.${index}.compare_at_price`]" />
            </div>

            <div class="grid gap-2">
              <Label for="cost_price">成本價</Label>
              <Input
                id="cost_price"
                type="number"
                v-model="variant.cost_price"
                @change="form.clearErrors('cost_price')"
              />
              <InputError :message="form.errors[`variants.${index}.cost_price`]" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>庫存</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid gap-2">
              <Label for="inventory_management">是否追蹤庫存數量</Label>
              <Select v-model="form.inventory_management">
                <SelectTrigger>
                  <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="store">追蹤庫存數量</SelectItem>
                  <SelectItem value="none">不追蹤庫存數量</SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="form.errors.inventory_management" />
            </div>
          </CardContent>

          <CardContent v-if="form.inventory_management === 'store'">
            <Table class="mt-4">
              <TableHeader>
                <TableRow>
                  <TableHead>地點</TableHead>
                  <TableHead>庫存數量</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody v-if="variant.inventories?.length > 0">
                <TableRow
                  v-for="(item, index) in variant.inventories"
                  :key="index"
                >
                  <TableCell>{{ item.location }}</TableCell>
                  <TableCell>
                    <Input
                      type="number"
                      v-model="item.quantity"
                    />
                  </TableCell>
                </TableRow>
                <TableRow>
                  <TableCell class="font-bold">共計</TableCell>
                  <TableCell
                    class="font-bold"
                    :class="{ 'text-red-500': variant.inventories.reduce((sum, item) => sum + Number(item.quantity), 0) < 0 }"
                  >
                    {{ variant.inventories.reduce((sum, item) => sum + Number(item.quantity), 0) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>

            <div
              v-if="variant.inventories?.length === 0"
              class="p-2 text-center w-full"
            >
              尚未設定地址
            </div>
          </CardContent>
        </Card>
      </template>
    </div>

    <div class="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>發佈狀態</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-2">
            <RadioGroup v-model="form.status">
              <div class="flex items-center space-x-2">
                <RadioGroupItem
                  id="published"
                  value="published"
                />
                <Label for="published">已發布</Label>
              </div>
              <div class="flex items-center space-x-2">
                <RadioGroupItem
                  id="unpublished"
                  value="unpublished"
                />
                <Label for="unpublished">未發布</Label>
              </div>
            </RadioGroup>
            <InputError :message="form.errors.status" />
          </div>
        </CardContent>
      </Card>

      <Card class="p-6 grid gap-4">
        <div class="grid gap-2">
          <Label for="categories">商品分類</Label>
          <Popover v-model:open="categoriesOpen">
            <PopoverTrigger as-child>
              <Button
                id="categories"
                variant="outline"
                role="combobox"
                :aria-expanded="categoriesOpen"
                class="w-full justify-between"
              >
                <span class="truncate">
                  {{ selectedCategoryNames.length > 0 
                    ? `${selectedCategoryNames.length} 個分類已選擇` 
                    : '選擇或新增分類' }}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[var(--radix-popover-trigger-width)] p-0" align="start">
              <Command>
                <CommandInput 
                  v-model="categorySearch"
                  placeholder="搜尋分類..." 
                />
                <CommandList>
                  <CommandEmpty>
                    <div v-if="canCreateNewCategory" class="py-2">
                      <Button
                        variant="ghost"
                        class="w-full justify-start"
                        @click="createNewCategory"
                      >
                        新增「{{ categorySearch.trim() }}」
                      </Button>
                    </div>
                    <span v-else>找不到分類</span>
                  </CommandEmpty>
                  <CommandGroup>
                    <CommandItem
                      v-for="option in filteredCategoryOptions"
                      :key="option.value"
                      :value="option.label"
                      @select="selectCategory(option.label)"
                    >
                      <Check
                        :class="cn(
                          'mr-2 h-4 w-4',
                          selectedCategoryNames.includes(option.label) ? 'opacity-100' : 'opacity-0'
                        )"
                      />
                      {{ option.label }}
                    </CommandItem>
                    <CommandItem
                      v-if="canCreateNewCategory"
                      @select="createNewCategory"
                    >
                      <span class="text-muted-foreground">新增「{{ categorySearch.trim() }}」</span>
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
          <div v-if="selectedCategoryNames.length > 0" class="flex flex-wrap gap-2 mt-2">
            <Badge
              v-for="categoryName in selectedCategoryNames"
              :key="categoryName"
              variant="secondary"
              class="pr-1"
            >
              {{ categoryName }}
              <button
                type="button"
                class="ml-1 rounded-full hover:bg-secondary-foreground/20"
                @click="removeCategory(categoryName)"
              >
                <X class="h-3 w-3" />
              </button>
            </Badge>
          </div>
          <InputError :message="form.errors.categories" />
        </div>

        <div class="grid gap-2">
          <Label for="brands">商品品牌</Label>
          <Popover v-model:open="brandsOpen">
            <PopoverTrigger as-child>
              <Button
                id="brands"
                variant="outline"
                role="combobox"
                :aria-expanded="brandsOpen"
                class="w-full justify-between"
              >
                <span class="truncate">
                  {{ selectedBrandNames.length > 0 
                    ? `${selectedBrandNames.length} 個品牌已選擇` 
                    : '選擇或新增品牌' }}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[var(--radix-popover-trigger-width)] p-0" align="start">
              <Command>
                <CommandInput 
                  v-model="brandSearch"
                  placeholder="搜尋品牌..." 
                />
                <CommandList>
                  <CommandEmpty>
                    <div v-if="canCreateNewBrand" class="py-2">
                      <Button
                        variant="ghost"
                        class="w-full justify-start"
                        @click="createNewBrand"
                      >
                        新增「{{ brandSearch.trim() }}」
                      </Button>
                    </div>
                    <span v-else>找不到品牌</span>
                  </CommandEmpty>
                  <CommandGroup>
                    <CommandItem
                      v-for="option in filteredBrandOptions"
                      :key="option.value"
                      :value="option.label"
                      @select="selectBrand(option.label)"
                    >
                      <Check
                        :class="cn(
                          'mr-2 h-4 w-4',
                          selectedBrandNames.includes(option.label) ? 'opacity-100' : 'opacity-0'
                        )"
                      />
                      {{ option.label }}
                    </CommandItem>
                    <CommandItem
                      v-if="canCreateNewBrand"
                      @select="createNewBrand"
                    >
                      <span class="text-muted-foreground">新增「{{ brandSearch.trim() }}」</span>
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
          <div v-if="selectedBrandNames.length > 0" class="flex flex-wrap gap-2 mt-2">
            <Badge
              v-for="brandName in selectedBrandNames"
              :key="brandName"
              variant="secondary"
              class="pr-1"
            >
              {{ brandName }}
              <button
                type="button"
                class="ml-1 rounded-full hover:bg-secondary-foreground/20"
                @click="removeBrand(brandName)"
              >
                <X class="h-3 w-3" />
              </button>
            </Badge>
          </div>
          <InputError :message="form.errors.brands" />
        </div>
      </Card>
    </div>
  </div>
</template>
