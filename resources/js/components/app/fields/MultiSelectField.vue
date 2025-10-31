<script setup>
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList, CommandSeparator } from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Separator } from '@/components/ui/separator'
import CheckIcon from '~icons/radix-icons/check'
import PlusCircledIcon from '~icons/radix-icons/plus-circled'

const props = defineProps({
  modelValue: Array,
  options: Array,
  title: String,
})

const emit = defineEmits([
  'update:modelValue',
])

const selectedValues = computed({
  get: () => props.modelValue || [],
  set: val => emit('update:modelValue', val),
})

const onSelect = option => {
  const isSelected = selectedValues.value.includes(option.value)

  if (isSelected) selectedValues.value = selectedValues.value.filter((value) => value !== option.value)

  else selectedValues.value.push(option.value)
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        size="sm"
        :disabled="!options"
        class="h-8 border-dashed"
      >
        <PlusCircledIcon class="mr-2 h-4 w-4" />
        {{ title }}
        <template v-if="selectedValues.length > 0">
          <Separator
            orientation="vertical"
            class="mx-2 h-4"
          />
          <Badge
            variant="secondary"
            class="rounded-sm px-1 font-normal lg:hidden"
          >
            {{ selectedValues.length }}
          </Badge>
          <div class="hidden space-x-1 lg:flex">
            <Badge
              v-if="selectedValues.length > 2"
              variant="secondary"
              class="rounded-sm px-1 font-normal"
            >
              {{ selectedValues.length }} selected
            </Badge>

            <template v-else>
              <Badge
                v-for="option in options?.filter((option) => selectedValues.includes(option.value))"
                :key="option.value"
                variant="secondary"
                class="rounded-sm px-1 font-normal"
              >
                {{ option.label }}
              </Badge>
            </template>
          </div>
        </template>
      </Button>
    </PopoverTrigger>

    <PopoverContent
      class="w-[200px] p-0"
      align="start"
    >
      <Command>
        <CommandInput :placeholder="title" />
        <CommandList class="h-[200px]">
          <CommandEmpty>No results found.</CommandEmpty>
          <CommandGroup>
            <CommandItem
              v-for="option in options"
              :key="option.value"
              :value="option"
              @select="onSelect(option)"
            >
              <div :class="cn(
                  'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                  selectedValues.includes(option.value)
                    ? 'bg-primary text-primary-foreground'
                    : 'opacity-50 [&_svg]:invisible',
                )">
                <CheckIcon :class="cn('h-4 w-4')" />
              </div>
              <span>{{ option.label }}</span>
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>

      <template v-if="selectedValues.length > 0">
        <hr>

        <div class="flex items-center justify-between p-1">
          <Button
            variant="ghost"
            class="w-full"
            @click="selectedValues = []"
          >
            Clear filters
          </Button>
        </div>
      </template>
    </PopoverContent>
  </Popover>
</template>
