<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { History } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'
import VariantTable from './components/VariantTable.vue'
import VariantType from './components/VariantType.vue'

// ------------------------------------------------
const props = defineProps({
  product: Object,
  locations: Array,
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
          <TagsInput
            id="categories"
            v-model="form.categories"
          >
            <TagsInputItem
              v-for="item in form.categories"
              :key="item"
              :value="item"
            >
              <TagsInputItemText />
              <TagsInputItemDelete />
            </TagsInputItem>

            <TagsInputInput placeholder="新增分類" />
          </TagsInput>
          <InputError :message="form.errors.categories" />
        </div>

        <div class="grid gap-2">
          <Label for="brands">商品品牌</Label>
          <TagsInput
            id="brands"
            v-model="form.brands"
          >
            <TagsInputItem
              v-for="item in form.brands"
              :key="item"
              :value="item"
            >
              <TagsInputItemText />
              <TagsInputItemDelete />
            </TagsInputItem>

            <TagsInputInput placeholder="新增品牌" />
          </TagsInput>
          <InputError :message="form.errors.brands" />
        </div>
      </Card>
    </div>
  </div>
</template>
