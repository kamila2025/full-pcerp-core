<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Search, X } from 'lucide-vue-next'
import MultiSelectField from '@/components/app/fields/MultiSelectField.vue'
import DateRangeField from '@/components/app/fields/DateRangeField.vue'
import SearchComboField from '@/components/app/fields/SearchComboField.vue'
import TextField from '@/components/app/fields/TextField.vue'
import NumberField from '@/components/app/fields/NumberField.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  fields: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const updateField = (key, value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [key]: value
  })
}

const resetFilters = () => {
  const reset = {}
  props.fields.forEach(field => {
    reset[field.key] = field.defaultValue
  })

  emit('update:modelValue', reset)
}

const parseFilters = (filters, fields) => {
  const query = {}
  const searchParts = []

  for (const field of fields) {
    const value = filters[field.key]

    // 過濾空值與 [""] 陣列
    if (
      value === '' ||
      value === null ||
      value === undefined ||
      (Array.isArray(value) && value.every(v => v === ''))
    ) continue

    if (field.type === 'search-combo') {
      const keywordField = filters[field.key]
      const keywordValue = filters[keywordField]

      if (!keywordField || keywordValue === undefined || keywordValue === '') continue

      if (field.queryMode === 'search') {
        searchParts.push(`${keywordField}:${keywordValue}`)
        query[field.key] = keywordField
      } else {
        query[keywordField] = keywordValue
        query[field.key] = keywordField
      }
      continue
    }

    if (field.type === 'date') {
      const dateRange = filters[field.key] || ['', '']
      const [fromTimestamp, toTimestamp] = dateRange

      if (fromTimestamp && toTimestamp) {
        if (field.queryMode === 'search') {
          searchParts.push(`${field.key}:${fromTimestamp},${toTimestamp}`)
        } else {
          query[field.key] = `${fromTimestamp},${toTimestamp}`
        }
      }
      continue
    }

    if (field.queryMode === 'search') {
      searchParts.push(`${field.key}:${value}`)
    } else {
      query[field.key] = Array.isArray(value) ? String(value).split(',').filter(Boolean).join(',') : value
    }
  }

  if (searchParts.length > 0) {
    query.search = searchParts.join(';')
    query.searchJoin = 'and'
  }

  return query
}

defineExpose({ resetFilters, parseFilters })
</script>

<template>
  <slot
    name="filter-panel"
    :fields="fields"
    :modelValue="modelValue"
    :update="updateField"
    :reset="resetFilters"
  >
    <div
      class1="grid grid-cols-1 md:grid-cols-4 gap-4 items-end"
      class="flex flex-wrap gap-1"
    >
      <template
        v-for="field in fields"
        :key="field.key"
      >
        <template v-if="$slots[field.key]">
          <slot
            :name="field.key"
            :modelValue="modelValue[field.key]"
            :update="(val) => updateField(field.key, val)"
          />
        </template>

        <template v-else>
          <!-- Text -->
          <div v-if="field.type === 'text'">
            <TextField
              :model-value="modelValue[field.key]"
              :label="field.label"
              :placeholder="field.placeholder || ''"
              @update:model-value="val => updateField(field.key, val)"
            />
          </div>

          <!-- Number -->
          <div v-else-if="field.type === 'number'">
            <NumberField
              type="number"
              :model-value="modelValue[field.key]"
              :label="field.label"
              @update:model-value="val => updateField(field.key, val)"
            />
          </div>

          <!-- MultiSelect -->
          <div v-else-if="field.type === 'multi-select'">
            <MultiSelectField
              :title="field.label"
              :model-value="modelValue[field.key]"
              :options="field.options"
              @update:model-value="val => updateField(field.key, val)"
            />
          </div>

          <!-- Search Combo -->
          <div v-else-if="field.type === 'search-combo'">
            <SearchComboField
              :selected-field="modelValue[field.key]"
              :field-value="modelValue[modelValue[field.key]]"
              :options="field.options"
              @update:selected-field="val => updateField(field.key, val)"
              @update:field-value="val => updateField(modelValue[field.key], val)"
            />
          </div>

          <!-- Date Range -->
          <div v-else-if="field.type === 'date'">
            <DateRangeField
              :model-value="modelValue[field.key]"
              :label="field.label"
              @update:model-value="val => updateField(field.key, val)"
            />
          </div>
        </template>
      </template>

      <Button
        v-if="Object.keys(parseFilters(modelValue, fields))?.length > 0"
        variant="ghost"
        class="h-8 px-2 lg:px-3"
        @click="resetFilters()"
      >
        重設
        <X class="ml-2 h-4 w-4" />
      </Button>
    </div>
  </slot>
</template>