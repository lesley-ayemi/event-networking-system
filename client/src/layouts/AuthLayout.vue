<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-b from-indigo-50 via-white to-white px-4">
    <RouterLink to="/" class="text-xl font-bold text-gray-900 tracking-tight">
      Event<span class="text-indigo-600">Networking</span>
    </RouterLink>

    <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md ring-1 ring-gray-100 overflow-hidden sm:rounded-2xl">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>
