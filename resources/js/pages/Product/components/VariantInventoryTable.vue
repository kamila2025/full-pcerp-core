<script setup>
import { ref } from 'vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'

const props = defineProps({
  modelValue: {
    type: Array,
    required: true,
  },
  locations: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['updateInventory'])

const variants = ref(props.modelValue)

const updateInventory = (variantIndex, locationId, quantity) => {
  emit('updateInventory', {
    variantIndex,
    locationId,
    quantity,
  })
}
</script>

<template>
  <div
    v-if="locations?.length === 0"
    class="p-2 text-center w-full"
  >
    尚未設定地址
  </div>

  <Table
    v-else
    class="mt-4"
  >
    <TableHeader>
      <TableRow>
        <TableHead class="text-nowrap">商品規格</TableHead>
        <TableHead
          v-for="location in locations"
          :key="location.id"
        >
          {{ location.name }}
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow
        v-for="(variant, variantIndex) in variants"
        :key="variantIndex"
      >
        <TableCell class="text-nowrap">{{ variant.values.map(item => item.name).join(' . ') }}</TableCell>
        <TableCell
          v-for="location in locations"
          :key="location.id"
        >
          <Input
            type="number"
            :model-value="variant.inventories.find(inv => inv.location_id === location.id)?.quantity"
            @update:model-value="updateInventory(variantIndex, location.id, $event)"
          />
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
</template>