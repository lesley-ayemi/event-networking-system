<template>
  <input
    :id="id"
    class="block w-full rounded-xl bg-white px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-gray-200 shadow-sm transition placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
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
