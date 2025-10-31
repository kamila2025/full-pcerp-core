<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Apple, MessageCircleMore, GalleryVerticalEnd } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import Layout from '@/layouts/AuthLayout.vue'

const props = defineProps({
  // Share Data
  appName: {
    type: String,
    default: () => import.meta.env.VITE_APP_NAME,
  },
  version: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}

defineOptions({ layout: Layout })
</script>

<template>
  <div class="flex w-full max-w-sm flex-col gap-6">
    <a
      href="#"
      class="flex items-center gap-2 self-center font-medium"
    >
      <img
        src="/public/images/logo/banner.png"
        alt="Logo"
        class="w-full h-20"
      />
    </a>

    <div class="flex flex-col gap-6">
      <Card>
        <CardHeader class="text-center">
          <CardTitle class="text-xl">
            登入店面系統
          </CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit">
            <div class="grid gap-6">
              <div class="flex flex-col gap-4">
                <Button
                  type="button"
                  variant="outline"
                  as="a"
                  href="/oauth2/redirect/line"
                  class="group relative w-full text-white hover:text-white active:text-white bg-[#06C755] hover:bg-[#06C755] active:bg-[#06C755] overflow-hidden"
                >
                  <div class="absolute inset-0 rounded-md bg-black opacity-0 group-hover:opacity-10 group-active:opacity-30 transition-opacity"></div>
                  <img
                    src="/public/images/icons/line.png"
                    alt="Line"
                    class="h-4 w-4"
                  />
                  使用 LINE 帳號登入
                </Button>
              </div>
              <div class="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
                <span class="relative z-10 bg-background px-2 text-muted-foreground">
                  或
                </span>
              </div>
              <div class="grid gap-6">
                <div class="grid gap-2">
                  <Label html-for="email">信箱</Label>
                  <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    placeholder="m@example.com"
                    required
                  />
                </div>
                <div class="grid gap-2">
                  <div class="flex items-center">
                    <Label html-for="password">密碼</Label>
                  </div>
                  <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                  />
                  <InputError :message="form.errors.email || $page.props?.errors?.email" />
                </div>

                <Button
                  type="submit"
                  :disabled="!form.email || !form.password"
                  class="w-full"
                >
                  登入
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
      <div class="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 [&_a]:hover:text-primary">
        <div>Powered by {{ appName }}</div>
        <div>{{ version }}</div>
      </div>
    </div>
  </div>
</template>
