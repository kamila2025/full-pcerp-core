<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { cn } from "@/lib/utils";

const props = defineProps({
  href: { type: String, required: false },
  target: { type: String, required: false },
  tabindex: { type: Number, required: false },
  method: { type: String, required: false },
  as: { type: String, required: false },
  class: { type: null, required: false },
});
</script>

<template>
  <Link
    :href="href"
    :target="target"
    :tabindex="tabindex"
    :method="method"
    :as="as"
    :class="cn('hover:!decoration-current text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out dark:decoration-neutral-500', props.class)"
  >
    <slot />
  </Link>
</template>
