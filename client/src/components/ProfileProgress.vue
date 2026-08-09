<template>
  <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5">
    <div class="flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-gray-900">Profile completion</h2>
      <span class="text-sm font-medium text-gray-500">{{ percent }}%</span>
    </div>

    <div class="w-full h-2 bg-gray-100 rounded-full mt-3 overflow-hidden">
      <div class="h-full bg-indigo-600 rounded-full" :style="{ width: `${percent}%` }" />
    </div>

    <ul v-if="incompleteItems.length > 0" class="mt-4 space-y-2">
      <li v-for="item in incompleteItems" :key="item.label" class="flex items-center justify-between text-sm">
        <span class="text-gray-600">{{ item.label }}</span>
        <RouterLink :to="item.to" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium whitespace-nowrap">
          Complete <span aria-hidden="true">&rarr;</span>
        </RouterLink>
      </li>
    </ul>
    <p v-else class="text-sm text-gray-500 mt-4">Your profile is complete. 🎉</p>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { RouterLink } from "vue-router";

const props = defineProps({
  user: { type: Object, required: true },
});

const CHECKLIST = [
  { label: "Add a profile photo", to: "/onboarding", isComplete: (user) => Boolean(user.profile_image) },
  {
    label: "Fill in your bio and job details",
    to: "/onboarding",
    isComplete: (user) => Boolean(user.bio && user.job_title && user.industry),
  },
  { label: "Take the compatibility quiz", to: "/quiz", isComplete: (user) => Object.keys(user.quiz_answers ?? {}).length > 0 },
  { label: "Finish onboarding", to: "/onboarding", isComplete: (user) => Boolean(user.onboarding_completed) },
];

const incompleteItems = computed(() => CHECKLIST.filter((item) => !item.isComplete(props.user)));
const percent = computed(() => Math.round(((CHECKLIST.length - incompleteItems.value.length) / CHECKLIST.length) * 100));
</script>
