<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <RouterLink to="/" class="text-xl font-semibold text-gray-800">
      Event Networking
    </RouterLink>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>
