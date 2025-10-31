<script setup>
import { Link as ILink } from '@inertiajs/vue3'
import { CreditCard, Smartphone, Monitor } from 'lucide-vue-next'

// ------------------------------------------------
const props = defineProps({
  authId: Number,
})
</script>

<template>
  <div>
    <div class="p-3 grid grid-cols-2 gap-3 text-center ">
      <a
        v-if="$can('installments')"
        :href="$route('installments.gomypay')"
      >
        <div class="p-4 flex flex-col rounded text-xs text-slate-600 bg-white">
          <CreditCard class="w-6 h-6 mx-auto" />
          <span class="mt-2">信用卡分期</span>
        </div>
      </a>

      <a
        v-if="$can('installments')"
        :href="$route('installments.m-majipay')"
      >
        <div class="p-4 flex flex-col rounded text-xs text-slate-600 bg-white">
          <Smartphone class="w-6 h-6 mx-auto" />
          <span class="mt-2">麻吉手機無卡</span>
        </div>
      </a>

      <a
        v-if="$can('installments')"
        :href="$route('installments.majipay')"
      >
        <div class="p-4 flex flex-col rounded text-xs text-slate-600 bg-white">
          <Monitor class="w-6 h-6 mx-auto" />
          <span class="mt-2">麻吉電腦無卡</span>
        </div>
      </a>

      <a
        v-if="$can('installments')"
        :href="$route('installments.zingala')"
      >
        <div class="p-4 flex flex-col rounded text-xs text-slate-600 bg-white">
          <Monitor class="w-6 h-6 mx-auto" />
          <span class="mt-2">中租電腦無卡</span>
        </div>
      </a>

      <a
        v-if="$can('installments')"
        :href="$route('installments.bobopay')"
      >
        <div class="p-4 flex flex-col rounded text-xs text-slate-600 bg-white">
          <CreditCard class="w-6 h-6 mx-auto" />
          <span class="mt-2">BOBOPay</span>
        </div>
      </a>
    </div>
  </div>
</template>