@@ -0,0 +1,81 @@
<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import AppHeader from '@/components/AppHeader.vue'
import InputError from '@/components/InputError.vue'

// ------------------------------------------------
const props = defineProps({
  user: Object,
})

const form = useForm({
  name: props.user.name || '',
  email: props.user.email || '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.put(`/users/${props.user.id}`, {
    //
  })
}
</script>


<template>
  <AppHeader
    tag="portal"
    :title="$t('Edit User')"
    is-back
    @backAction="router.get('/users')"
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

  <form
    @keyup.enter="submit"
    class="flex flex-col gap-6"
  >
    <div class="grid gap-2">
      <Label for="name">名稱</Label>
      <Input
        id="name"
        v-model="form.name"
        autofocus
        autocomplete="name"
        @change="form.clearErrors('name')"
      />
      <InputError :message="form.errors.name" />
    </div>

    <div class="grid gap-2">
      <Label for="email">信箱</Label>
      <Input
        id="email"
        v-model="form.email"
        autocomplete="email"
        @change="form.clearErrors('email')"
      />
      <InputError :message="form.errors.email" />
    </div>

    <div class="grid gap-2">
      <Label for="password">密碼</Label>
      <Input
        id="password"
        type="password"
        autocomplete="new-password"
        v-model="form.password"
        @change="form.clearErrors('password')"
      />
      <InputError :message="form.errors.password" />
    </div>

    <div class="grid gap-2">
      <Label for="password_confirmation">確認密碼</Label>
      <Input
        id="password_confirmation"
        type="password"
        autocomplete="new-password"
        v-model="form.password_confirmation"
        @change="form.clearErrors('password_confirmation')"
      />
      <InputError :message="form.errors.password_confirmation" />
    </div>
  </form>
</template>
