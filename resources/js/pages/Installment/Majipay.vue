<template>
  <div class="pt-2 space-y-5">
    <div class="flex items-center mb-4">
      <label class="mr-2">撥款金額</label>
      <input
        type="number"
        v-model="form.initial_amount"
        @change="onSubmit"
        class="border rounded px-2 py-1 w-full"
      />
    </div>

    <table class="bg-white text-center w-full">
      <thead>
        <tr>
          <th class="border border-slate-300 ...">期數</th>
          <th class="border border-slate-300 ...">月付款</th>
          <th class="border border-slate-300 ...">貸款金額</th>
          <th class="border border-slate-300 ...">實際撥款</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(value, index) in loanValues"
          :key="index"
        >
          <td class="border border-slate-300 ...">{{ value.instllment_count }}</td>
          <td class="border border-slate-300 ...">{{ value.total_handling_charge }}</td>
          <td class="border border-slate-300 ...">{{ value.total_pay_amount }}</td>
          <td class="border border-slate-300 ...">{{ value.transfer_amount }}</td>
        </tr>
      </tbody>
    </table>

    <hr>

    <div class="flex items-center mb-4">
      <label class="mr-2">輸入期數</label>
      <select
        v-model="form.installment_count"
        class="border rounded px-2 py-1 w-full outline-none"
        @change="onSubmit"
      >
        <option
          v-for="i in installments"
          :key="i"
        >
          {{ i }}
        </option>
      </select>
    </div>

    <div class="flex items-center mb-4">
      <label class="mr-2">輸入月付</label>
      <input
        type="number"
        v-model="form.term_amount"
        @change="onSubmit"
        class="border rounded px-2 py-1 w-full"
      />
    </div>

    <div class="flex items-center mb-4">
      <label class="mr-2">貸款金額</label>
      <input
        type="text"
        :value="form.installment_count * form.term_amount"
        readonly
        class="border rounded px-2 py-1 w-full bg-gray-100"
      />
    </div>

    <div class="flex items-center mb-4">
      <label class="mr-2">撥款金額</label>
      <input
        type="text"
        :value="transferAmount"
        readonly
        class="border rounded px-2 py-1 w-full bg-gray-100"
      />
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { debounce } from 'lodash'

// ------------------------------------------------
const props = defineProps({
  loanValues: {
    type: Array,
    default: [],
  },
  initialLoanAmount: {
    type: Number,
    default: 0,
  },
  installmentCount: {
    type: Number,
    default: 0,
  },
  termAmount: {
    type: Number,
    default: 0,
  },
  installments: {
    type: Array,
    default: [],
  },
  transferAmount: {
    type: Number,
    default: 0,
  },
})

// 資料定義
const form = useForm({
  initial_amount: props.initialLoanAmount,
  installment_count: props.installmentCount,
  term_amount: props.termAmount,
})

const onSubmit = debounce(() => {
  form.get(route(route().current()), { replace: true })
}, 600)
</script>

<style>
</style>
