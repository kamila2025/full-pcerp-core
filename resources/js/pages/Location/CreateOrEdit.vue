<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Switch } from '@/components/ui/switch'
import MultiSelectField from '@/components/app/fields/MultiSelectField.vue'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'

// ------------------------------------------------
const props = defineProps({
  location: Object,
  channels: Array,
})

const form = useForm({
  name: props.location?.name || '',
  code: props.location?.code || '',
  areaCode: '',
  country: '',
  city: '',
  district: '',
  town: '',
  zip: '',
  address1: props.location?.address1 || '',
  address2: props.location?.address2 || '',
  phone: props.location?.phone || '',
  email: props.location?.email || '',
  // 通知設定
  channels: props.location?.channels?.map(channel => channel.id) || [],
})

const submit = () => {
  if (props.location) form.put(`/locations/${props.location.id}`)

  else form.post('/locations')
}

// 將頻道資料轉換為 MultiSelectField 需要的格式
const notificationChannels = props.channels?.map(channel => ({
  value: channel.id,
  label: `[${channel.type}] ${channel.name}`,
})) || []
</script>

<template>
  <AppHeader
    tag="portal"
    :title="location ? '編輯據點地址' : '新增據點地址'"
    is-back
    @back-action="router.get('/locations')"
  >
    <template #actions>
      <Button
        variant="secondary"
        @click="submit"
      >
        Save
      </Button>
    </template>
  </AppHeader>

  <div class="flex flex-col md:flex-row gap-8">
    <!-- 左側說明 -->
    <div class="w-full md:w-1/3">
      <h3 class="font-semibold mb-2">地址</h3>
      <p class="text-sm text-gray-500">請輸入地址</p>
    </div>

    <!-- 右側表單區域 -->
    <div class="w-full md:w-2/3 space-y-6">
      <!-- 地址資訊卡片 -->
      <Card class="p-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="grid gap-2 self-baseline">
            <Label for="name">地點名稱<span class="text-red-500 ml-1">*</span></Label>
            <Input
              id="name"
              v-model="form.name"
              @change="form.clearErrors('name')"
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="grid gap-2 self-baseline">
            <Label for="code">地點代碼<span :class="{ 'text-red-500 ml-1': location, 'hidden': !location }">*</span></Label>
            <Input
              id="code"
              v-model="form.code"
              @change="form.clearErrors('code')"
            />
            <InputError :message="form.errors.code" />
          </div>

          <div class="grid gap-2 col-span-2 self-baseline">
            <Label for="town">鄉/鎮/市/區</Label>
            <Input
              id="town"
              v-model="form.town"
              @change="form.clearErrors('town')"
            />
            <InputError :message="form.errors.town" />
          </div>

          <div class="grid gap-2 col-span-2 self-baseline">
            <Label for="address1">地址第一欄<span class="text-red-500 ml-1">*</span></Label>
            <Input
              id="address1"
              v-model="form.address1"
              @change="form.clearErrors('address1')"
            />
            <InputError :message="form.errors.address1" />
          </div>

          <div class="grid gap-2 col-span-2 self-baseline">
            <Label for="address2">地址第二欄</Label>
            <Input
              id="address2"
              v-model="form.address2"
              @change="form.clearErrors('address2')"
            />
            <InputError :message="form.errors.address2" />
          </div>

          <div class="grid gap-2 self-baseline">
            <Label for="phone">電話</Label>
            <Input
              id="phone"
              v-model="form.phone"
              @change="form.clearErrors('phone')"
            />
            <InputError :message="form.errors.phone" />
          </div>

          <div class="grid gap-2 self-baseline">
            <Label for="email">電子郵件</Label>
            <Input
              id="email"
              v-model="form.email"
              @change="form.clearErrors('email')"
            />
            <InputError :message="form.errors.email" />
          </div>
        </div>
      </Card>

      <!-- 通知設定卡片 -->
      <Card class="p-6">
        <div class="flex flex-col md:flex-row gap-8">
          <!-- 左側：通知說明 -->
          <div class="w-full md:w-1/2">
            <h3 class="text-lg font-semibold mb-2">通知設定</h3>
            <p class="text-sm text-gray-500 mb-4">
              您可以在這裡設定通知頻道，以便在觸發相關事件時收到通知。
            </p>
            <a
              href="/channels"
              class="text-blue-600 hover:text-blue-800 underline text-sm"
            >
              設定通知頻道
            </a>
          </div>

          <!-- 右側：通知設定 -->
          <div class="w-full md:w-1/2">
            <div class="space-y-4">
              <!-- 通知頻道選擇 -->
              <div class="space-y-3">
                <Label class="text-sm font-medium">選擇接收通知的頻道</Label>

                <div class="min-h-[32px]">
                  <MultiSelectField
                    v-model="form.channels"
                    :options="notificationChannels"
                    title="選擇通知頻道"
                  />
                </div>
                <p class="text-xs text-gray-500">
                  選擇此據點要接收通知的頻道。您可以在通知頻道頁面管理所有可用的頻道。
                </p>
              </div>

              <!-- 通知說明 -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">通知類型</h4>
                <div class="text-xs text-blue-700 space-y-1">
                  <p>• <strong>訂單通知：</strong>新訂單、訂單狀態變更</p>
                  <p>• <strong>庫存警報：</strong>庫存不足、庫存變動</p>
                  <p>• <strong>客戶更新：</strong>客戶資料變更、新客戶註冊</p>
                  <p>• <strong>系統維護：</strong>系統更新、維護通知</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
