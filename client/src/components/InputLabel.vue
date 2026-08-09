<template>
  <label :for="forId" class="block font-medium text-sm text-gray-700 mb-1.5">
    <slot>{{ value }}</slot>
  </label>
</template>

<script setup>
defineProps({
  value: { type: String, default: "" },
  forId: { type: String, default: "" },
});
</script>
