<template>
  <input
    type="checkbox"
    :checked="modelValue"
    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    @change="$emit('update:modelValue', $event.target.checked)"
  />
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
});
defineEmits(["update:modelValue"]);
</script>
