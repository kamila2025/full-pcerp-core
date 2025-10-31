import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { abilitiesPlugin } from '@casl/vue'

import ability from '@/lib/acl/ability'
import router from '@/lib/router'
import i18n from '@/lib/i18n'
import Layout from './layouts/AppLayout.vue'

// 3rd party plugins
import PortalVue from 'portal-vue'
import FloatingVue from 'floating-vue'

// Moment Locale Registration
import 'moment/dist/locale/zh-tw'
import 'floating-vue/dist/style.css'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => title ? `${title} - ${appName}` : appName,
  resolve: name => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    let page = pages[`./pages/${name}.vue`]
    page.default.layout = page.default.layout || Layout
    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(router)
      .use(i18n)
      .use(plugin)
      .use(PortalVue)
      .use(FloatingVue, { autoHideOnMousedown: true })
      .use(abilitiesPlugin, ability, {
        useGlobalProperties: true
      })
      .mount(el)
  },
})