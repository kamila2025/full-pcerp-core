<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  modelValue: { type: null, required: false },
  label: { type: String, default: 'Select' },
})

const emit = defineEmits([
  'update:modelValue',
])

const open = ref(false)

const form = useForm({
  name: '',
  email: '',
  phone: '',
  additional: {},
})

const submit = () => {
  if (!form.name?.trim()) {
    form.setError('name', '此欄位為必填')
    return
  }

  if (!form.additional?.customer_source?.trim()) {
    form.setError('additional.customer_source', '此欄位為必填')
    return
  }


  if (!form.phone?.trim()) {
    form.setError('phone', '此欄位為必填')
    return
  }

  open.value = false

  emit('update:modelValue', Object.assign({}, props.modelValue, form.data()))
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <slot />
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ label }}</DialogTitle>
        <DialogDescription></DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div class="col-1">
            <Label for="name">客戶名字*</Label>
            <Input
              id="name"
              v-model="form.name"
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="col-1">
            <Label for="customer_source">客戶來源*</Label>
            <Select v-model="form.additional.customer_source">
              <SelectTrigger id="gateway_id">
                <SelectValue placeholder="選擇來源" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="line">LINE</SelectItem>
                <SelectItem value="facebook">Facebook</SelectItem>
                <SelectItem value="instagram">Instagram</SelectItem>
                <SelectItem value="referral">轉介紹</SelectItem>
                <SelectItem value="relation">緣故</SelectItem>
                <SelectItem value="store">店面客</SelectItem>
                <SelectItem value="zingala">銀角商城</SelectItem>
                <SelectItem value="website">官網</SelectItem>
                <SelectItem value="outbound">陌生開發</SelectItem>
                <SelectItem value="other">其他</SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors['additional.customer_source']" />
          </div>

          <div class="col-1">
            <Label for="email">電子郵件</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
            />
          </div>

          <div class="col-1">
            <Label for="phone">電話號碼*</Label>
            <Input
              id="phone"
              type="tel"
              v-model="form.phone"
            />
            <InputError :message="form.errors.phone" />
          </div>

          <div class="col-1">
            <Label for="additional_id_number">身份證字號</Label>
            <Input
              id="additional_id_number"
              v-model="form.additional.id_number"
            />
          </div>

          <div class="col-1">
            <Label for="additional_carrier">門號電信</Label>
            <Select v-model="form.additional.carrier">
              <SelectTrigger id="gateway_id">
                <SelectValue placeholder="選擇電信" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="cht">中華電信</SelectItem>
                <SelectItem value="fareastone">遠傳電信</SelectItem>
                <SelectItem value="taiwan_mobile">台灣大哥大</SelectItem>
                <SelectItem value="gt">亞太電信</SelectItem>
                <SelectItem value="other">其他</SelectItem>
              </SelectContent>
            </Select>
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
