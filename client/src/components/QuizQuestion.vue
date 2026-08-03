<template>
  <div>
    <p class="text-sm font-medium text-gray-900 mb-2">{{ number }}. {{ question.text }}</p>
    <div class="space-y-1">
      <label v-for="option in question.options" :key="option.value" class="flex items-center text-sm text-gray-700">
        <input
          type="radio"
          :name="question.key"
          :value="option.value"
          :checked="modelValue === option.value"
          class="text-indigo-600 focus:ring-indigo-500 border-gray-300"
          @change="$emit('update:modelValue', option.value)"
        />
        <span class="ms-2">{{ option.label }}</span>
      </label>
    </div>
  </div>
</template>

<script setup>
defineProps({
  question: { type: Object, required: true },
  modelValue: { type: Number, default: null },
  number: { type: Number, required: true },
});
defineEmits(["update:modelValue"]);
</script>
