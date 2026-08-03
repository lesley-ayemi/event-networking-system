<template>
  <textarea
    :id="id"
    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>

<script setup>
defineProps({
  id: { type: String, default: "" },
  modelValue: { type: String, default: "" },
});
defineEmits(["update:modelValue"]);
</script>
