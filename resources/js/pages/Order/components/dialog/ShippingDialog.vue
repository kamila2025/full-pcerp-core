<script setup>
import { ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Pencil } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  modelValue: { type: null, required: false },
  label: { type: String, default: '' },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const form = useForm({
  full_name: '',
  company: '',
  address1: '',
  email: '',
  phone: '',
})

const submit = () => {
  open.value = false

  emit('update:modelValue', Object.assign({}, props.modelValue, form.data()))
}

watch(open, (newValue) => {
  form.full_name = props.modelValue?.full_name || ''
  form.company = props.modelValue?.company || ''
  form.address1 = props.modelValue?.address1 || ''
  form.email = props.modelValue?.email || ''
  form.phone = props.modelValue?.phone || ''
})
</script>

<template>
  <div
    v-if="modelValue"
    class="text-sm text-muted-foreground flex justify-between w-full"
  >
    <div>
      <div class="text-foreground font-semibold">{{ modelValue.full_name }}</div>
      <div>{{ modelValue.company }}</div>
      <div>{{ modelValue.address1 }}</div>
      <div>{{ modelValue.phone }}</div>
      <div>{{ modelValue.email }}</div>
    </div>

    <Pencil
      class="w-4 h-4 text-muted-foreground hover:text-foreground cursor-pointer"
      @click="open = true"
    />
  </div>

  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <slot v-if="!modelValue" />
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ label }}</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div class="col-1">
            <Label for="full_name">名字</Label>
            <Input
              id="full_name"
              v-model="form.full_name"
            />
          </div>

          <div class="col-1">
            <Label for="company">公司名稱</Label>
            <Input
              id="company"
              v-model="form.company"
            />
          </div>

          <div class="col-span-1 md:col-span-2">
            <Label for="address1">地址第一欄</Label>
            <Input
              id="address1"
              v-model="form.address1"
            />
          </div>

          <div class="col-1">
            <Label for="email">電子郵件</Label>
            <Input
              id="email"
              v-model="form.email"
            />
          </div>

          <div class="col-1">
            <Label for="phone">電話號碼</Label>
            <Input
              id="phone"
              v-model="form.phone"
            />
          </div>
        </div>

        <!-- 儲存按鈕 -->
        <div class="text-right mt-4">
          <Button type="submit">儲存</Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
