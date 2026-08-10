<template>
  <div class="h-16 flex items-center px-5 shrink-0 border-b border-gray-800">
    <RouterLink to="/admin" class="text-sm font-bold text-white tracking-tight" @click="$emit('navigate')">
      Event<span class="text-indigo-400">Networking</span>
    </RouterLink>
  </div>

  <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
    <div v-for="group in navGroups" :key="group.label">
      <p class="px-3 mb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ group.label }}</p>
      <RouterLink
        v-for="link in group.links"
        :key="link.to"
        :to="link.to"
        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium mb-0.5 transition"
        :class="isActive(link.to) ? 'bg-indigo-600 !text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
        @click="$emit('navigate')"
      >
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" :d="link.icon" />
        </svg>
        {{ link.label }}
      </RouterLink>
    </div>
  </nav>

  <div class="p-3 border-t border-gray-800 shrink-0">
    <RouterLink
      to="/dashboard"
      class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white"
      @click="$emit('navigate')"
    >
      &larr; Back to app
    </RouterLink>
  </div>
</template>

<script setup>
import { RouterLink, useRoute } from "vue-router";
import { matchesAdminLink } from "../../constants/adminNav.js";

defineProps({
  navGroups: { type: Array, required: true },
});
defineEmits(["navigate"]);

const route = useRoute();

function isActive(to) {
  return matchesAdminLink(route.path, to);
}
</script>
