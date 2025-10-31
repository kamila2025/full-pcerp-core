<script setup>
import { ref } from 'vue'
import {
  Settings,
  WalletCards,
  DollarSign,
  ShoppingCart,
  Users,
  Truck,
  MapPin,
  Calculator,
  Mail,
  FolderOpen,
  UserCog,
  Store,
  CreditCard,
  ChevronRight,
  Bell
} from 'lucide-vue-next'
import AppHeader from '@/components/AppHeader.vue'

const props = defineProps({
  auth: Object,
})

const settingCategories = ref([
  {
    title: '模板設定',
    description: '設定與管理訂單表單的欄位與格式',
    icon: FolderOpen,
    route: '/admin/form-templates'
  },
  {
    title: '收款設定',
    description: '設置您的收款方式以及欲使用的金流服務',
    icon: WalletCards,
    route: '/admin/gateways'
  },
  {
    title: '物流設定',
    description: '設置您所使用的物流服務、區域以及運費',
    icon: Truck,
    route: '/admin/logistics'
  },
  {
    title: '地址設定',
    description: '管理您的門市以及取貨地址',
    icon: MapPin,
    route: '/locations'
  },
  {
    title: '通知設定',
    description: '設定系統通知選項',
    icon: Bell,
    route: '/channels'
  }
]);
</script>

<template>
  <AppHeader
    tag="portal"
    :title="`設定`"
  />

  <!-- Info Card -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6 shadow-sm h-[130px]">
    <div class="flex items-start justify-between">
      <div class="flex items-start space-x-4">
        <UserCog class="w-6 h-6 text-gray-500" />
        <div>
          <h2 class="text-lg font-medium">{{ auth.user.name }}</h2>
          <p class="text-sm text-gray-500 mt-1">{{ auth.user.email }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Settings Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <a
      v-for="category in settingCategories"
      :key="category.title"
      :href="category.route"
      class="group block p-4 bg-white rounded-lg border border-gray-200 shadow-sm transition-all duration-200 hover:shadow-md"
    >
      <div class="flex items-start space-x-3">
        <component
          :is="category.icon"
          class="w-5 h-5 text-gray-500 mt-1 transition-transform duration-200 group-hover:scale-110"
        />
        <div>
          <h3 class="text-base font-medium text-gray-900">
            {{ category.title }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 leading-snug">
            {{ category.description }}
          </p>
        </div>
      </div>
    </a>
  </div>
</template>
