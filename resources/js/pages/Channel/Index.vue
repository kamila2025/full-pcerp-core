<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { SmartTable } from '@/components/app/table'
import { Trash2 } from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'

// ------------------------------------------------
const props = defineProps({
  channels: Object,
})

const form = useForm({
  type: 'telegram',
  name: '',
  settings: {
    //
  }
})

const tableColumns = [
  {
    accessorKey: 'name',
    header: '標籤',
  },
  {
    accessorKey: 'action',
    header: '',
    class: 'text-right w-0 whitespace-nowrap',
  },
]

const onDeleting = row => {
  if (confirm('確定要刪除此頻道嗎？')) {
    router.delete(`${props.channels.path}/${row.id}`)
  }
}

const onSubmit = () => {
  form.post('/channels', {
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>

<template>
  <AppHeader
    tag="portal"
    title="通知頻道"
  >
  </AppHeader>

  <div class="space-y-8">
    <!-- 創建通知頻道區塊 -->
    <Card class="p-6">
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">創建通知頻道</h3>
        <p class="text-sm text-gray-500">
          您可以在這裡定義通知頻道，用於監控通知、伺服器更新等各種用途。
        </p>
      </div>

      <form
        @submit.prevent="onSubmit"
        class="space-y-4"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- 類型選擇 -->
          <div>
            <Label for="type">類型</Label>
            <Select
              v-model="form.type"
              disabled
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="telegram">
                  <span class="flex items-center gap-2">
                    <span>📱</span>
                    <span>Telegram</span>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- 標籤 -->
          <div>
            <Label for="name">標籤</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="輸入頻道標籤"
              @change="form.clearErrors('name')"
            />
            <InputError :message="form.errors.name" />
          </div>

          <!-- Telegram Chat ID -->
          <div>
            <Label for="channel">Telegram Chat ID</Label>
            <Input
              id="channel"
              v-model="form.settings.telegram_chat_id"
              placeholder="輸入 Telegram Chat ID"
              @change="form.clearErrors('settings.telegram_chat_id')"
            />
            <InputError :message="form.errors['settings.telegram_chat_id']" />
          </div>

          <!-- Telegram Bot Token -->
          <div>
            <Label for="channel">Telegram Bot Token</Label>
            <Input
              id="channel"
              v-model="form.settings.telegram_bot_token"
              placeholder="輸入 Telegram Bot Token"
              @change="form.clearErrors('settings.telegram_bot_token')"
            />
            <InputError :message="form.errors['settings.telegram_bot_token']" />
          </div>

        </div>

        <div class="flex justify-end">
          <Button type="submit">
            創建
          </Button>
        </div>
      </form>
    </Card>

    <!-- 通知頻道列表 -->
    <Card class="p-4">
      <SmartTable
        :columns="tableColumns"
        :rows="channels.data"
        :per-page="channels.per_page"
        :page="channels.current_page"
        :total="channels.total"
        @onPageChange="page => router.get(channels.path, { page }, { preserveState: true })"
      >
        <template #cell(action)="data">
          <Button
            variant="ghost"
            size="sm"
            class="text-red-500 hover:text-red-700"
            @click="onDeleting(data.row)"
          >
            <Trash2 class="w-4 h-4" />
          </Button>
        </template>
      </SmartTable>
    </Card>
  </div>
</template>
