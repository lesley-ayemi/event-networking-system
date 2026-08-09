<template>
  <div class="relative">
    <select
      :value="modelValue"
      v-bind="$attrs"
      class="w-full appearance-none rounded-xl bg-white py-2.5 pl-3.5 pr-9 text-sm text-gray-900 ring-1 ring-gray-200 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
      :class="dark ? 'bg-white/10 text-white ring-white/30 [color-scheme:dark] focus:ring-white/60' : ''"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <slot />
    </select>
    <svg
      class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2"
      :class="dark ? 'text-white/70' : 'text-gray-400'"
      viewBox="0 0 20 20"
      fill="currentColor"
      aria-hidden="true"
    >
      <path
        fill-rule="evenodd"
        d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
        clip-rule="evenodd"
      />
    </svg>
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false });
defineProps({
  modelValue: { type: String, default: "" },
  dark: { type: Boolean, default: false },
});
defineEmits(["update:modelValue"]);
</script>
