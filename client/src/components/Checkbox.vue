<template>
  <input
    type="checkbox"
    :checked="modelValue"
    class="h-4 w-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0"
    @change="$emit('update:modelValue', $event.target.checked)"
  />
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
});
defineEmits(["update:modelValue"]);
</script>
