<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/InputError.vue'

const props = defineProps({
  order: { type: null },
})

const isOpen = ref(false)

const gatewaies = ref([])

const form = useForm({
  gateway_id: null,
  method: null,
  type: null,
  amount: 0,
  reference: '',
  note: '',
  additional: {},
})

const submitPayment = () => {
  form.post(`/orders/${props.order.id}/transactions`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.location.reload()
    },
    onError: () => {
      console.error('付款失敗')
    }
  })
}

const fetchData = open => {
  form.reset()

  router.reload({
    only: ['gatewaies'],
    prefetch: true,
    onPrefetched: response => {
      const page = JSON.parse(response.data)

      gatewaies.value = page.props?.gatewaies || []
    },
  })
}
</script>

<template>
  <Dialog
    v-model:open="isOpen"
    @update:open="fetchData"
  >
    <DialogTrigger as-child>
      <Button
        variant="secondary"
        size="sm"
      >
        更改為「已付款」
      </Button>
    </DialogTrigger>

    <DialogContent class="sm:max-w-2xl grid-rows-[auto_minmax(0,1fr)_auto] max-h-[90dvh]">
      <DialogHeader>
        <DialogTitle>結帳設定</DialogTitle>
      </DialogHeader>

      <form
        @submit.prevent="submitPayment"
        class="space-y-4 overflow-y-auto"
      >
        <!-- 付款類型 -->
        <div>
          <Label for="gateway_id">付款類型</Label>
          <Select>
            <SelectTrigger id="gateway_id">
              <SelectValue placeholder="選擇付款類型" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="(gateway, index) in gatewaies"
                :key="index"
                :value="gateway.id"
                @select="() => {
                  form.gateway_id = gateway.id
                  form.type = gateway.type
                }"
              >
                {{ gateway.title }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.gateway_id" />
        </div>

        <!-- 付款方式 -->
        <div v-if="gatewaies.find(f => f.id === form.gateway_id)?.sub_gateways">
          <Label for="method">付款方式</Label>
          <Select v-model="form.method">
            <SelectTrigger id="method">
              <SelectValue placeholder="付款方式" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="(method, index) in gatewaies.find(f => f.id === form.gateway_id)?.sub_gateways || []"
                :key="index"
                :value="method.gateway_method"
              >
                {{ method.title }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.method" />
        </div>

        <!-- 金額輸入框 -->
        <div>
          <Label for="amount">金額</Label>
          <div class="relative">
            <span class="absolute left-3 top-2.5 text-sm text-muted-foreground">TWD</span>
            <Input
              id="amount"
              v-model="form.amount"
              type="number"
              class="pl-12"
            />
            <InputError :message="form.errors.amount" />
          </div>
        </div>

        <div v-if="form.type === 'bank-transfer'">
          <Label for="reference">匯款帳號</Label>
          <div class="relative">
            <Input
              id="reference"
              v-model="form.reference"
            />
            <InputError :message="form.errors.reference" />
          </div>
        </div>

        <div v-if="form.type === 'installment'">
          <Label for="additional_period">期數</Label>
          <div class="relative">
            <Input
              id="additional_period"
              v-model="form.additional.period"
            />
            <InputError :message="form.errors['additional.period']" />
          </div>
        </div>

        <div v-if="form.type === 'installment'">
          <Label for="additional_amount">月付金</Label>
          <div class="relative">
            <Input
              id="additional_amount"
              v-model="form.additional.amount"
            />
            <InputError :message="form.errors['additional.amount']" />
          </div>
        </div>

        <div>
          <Label for="note">備註</Label>
          <div class="relative">
            <Textarea
              id="note"
              v-model="form.note"
            />
            <InputError :message="form.errors.note" />
          </div>
        </div>

        <!-- 確認付款 -->
        <DialogFooter class="flex items-center">
          <Button
            type="submit"
            :disabled="form.processing"
          >
            確認付款
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>