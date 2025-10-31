import { router } from '@inertiajs/vue3'
import ability from '@/lib/acl/ability'

export default {
  install(app) {
    app.config.globalProperties.$route = route

    router.on('navigate', (event) => {
      const { auth } = event.detail.page.props

      const permissions = auth.abilities?.map(permission => typeof permission === 'string' ? { action: permission } : permission)

      if (permissions) ability.update(permissions)
    })
  }
}
