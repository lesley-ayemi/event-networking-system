<template>
  <p v-if="message" class="text-sm text-red-600 mt-2" role="alert">{{ message }}</p>
</template>

<script setup>
defineProps({
  message: { type: String, default: "" },
});
</script>
