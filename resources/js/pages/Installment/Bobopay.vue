<template>
  <div class="pt-2 space-y-5">
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">撥款金額</label>
      <input
        type="number"
        v-model="form.initial_amount"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        @input="onSubmit"
      />
    </div>

    <table class="bg-white text-center w-full">
      <thead>
        <tr>
          <th class="border border-slate-300 ..." rowspan="2">期數</th>
          <th class="border border-slate-300 ..." colspan="2">BOBO A</th>
          <th class="border border-slate-300 ..." rowspan="2">期數</th>
          <th class="border border-slate-300 ..." colspan="2">BOBO T</th>
          <!-- <th class="border border-slate-300 ...">實際撥款</th> -->
        </tr>
        <tr>
          <th class="border border-slate-300 ...">月付款</th>
          <th class="border border-slate-300 ...">貸款金額</th>
          <th class="border border-slate-300 ...">月付款</th>
          <th class="border border-slate-300 ...">貸款金額</th>
          <!-- <th class="border border-slate-300 ...">實際撥款</th> -->
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
          <td class="border border-slate-300 ...">{{ value.instllment_count }}</td>
          <td class="border border-slate-300 ...">{{ value.t_total_handling_charge }}</td>
          <td class="border border-slate-300 ...">{{ value.t_total_pay_amount }}</td>
          <!-- <td class="border border-slate-300 ...">{{ value.transfer_amount }}</td> -->
        </tr>
      </tbody>
    </table>
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
})

// 資料定義
const form = useForm({
  initial_amount: props.initialLoanAmount,
})

const onSubmit = debounce(() => {
  form.get(route(route().current()), { replace: true })
}, 600)
</script>

<style>
</style>
