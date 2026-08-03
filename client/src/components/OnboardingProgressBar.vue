<template>
  <ol class="flex items-start w-full mb-8">
    <li v-for="(step, index) in steps" :key="step" class="flex items-center" :class="{ 'flex-1': index !== steps.length - 1 }">
      <div class="flex flex-col items-center shrink-0">
        <span
          class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold shrink-0"
          :class="badgeClass(index)"
        >
          <svg v-if="index + 1 < currentStep" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else>{{ index + 1 }}</span>
        </span>
        <span
          class="mt-2 text-xs font-medium text-center w-20"
          :class="index + 1 === currentStep ? 'text-indigo-600' : 'text-gray-500'"
        >
          {{ step }}
        </span>
      </div>
      <div v-if="index !== steps.length - 1" class="flex-1 h-0.5 mx-2 mt-4" :class="index + 1 < currentStep ? 'bg-indigo-600' : 'bg-gray-200'" />
    </li>
  </ol>
</template>

<script setup>
const props = defineProps({
  steps: { type: Array, required: true },
  currentStep: { type: Number, required: true },
});

function badgeClass(index) {
  if (index + 1 < props.currentStep) return "bg-indigo-600 text-white";
  if (index + 1 === props.currentStep) return "bg-indigo-100 text-indigo-600 ring-2 ring-indigo-600";
  return "bg-gray-100 text-gray-400";
}
</script>
