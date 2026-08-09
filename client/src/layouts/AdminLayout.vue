<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-gray-900 shadow-sm sticky top-0 z-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
          <span class="text-sm font-bold text-white tracking-tight whitespace-nowrap shrink-0">
            Event<span class="text-indigo-400">Networking</span> <span class="text-gray-500 font-medium">/ Admin</span>
          </span>

          <div class="hidden md:flex items-center gap-6">
            <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">
              {{ link.label }}
            </RouterLink>
            <RouterLink to="/dashboard" class="text-sm font-medium text-gray-400 hover:text-white whitespace-nowrap">
              &larr; Back to app
            </RouterLink>
          </div>

          <button
            type="button"
            class="md:hidden text-gray-300 hover:text-white p-1"
            :aria-expanded="isMobileMenuOpen"
            aria-label="Toggle menu"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="isMobileMenuOpen" class="md:hidden pb-4 space-y-1 border-t border-gray-700 pt-3">
          <RouterLink
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white"
          >
            {{ link.label }}
          </RouterLink>
          <RouterLink to="/dashboard" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white">
            &larr; Back to app
          </RouterLink>
        </div>
      </div>
    </nav>
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";

const navLinks = [
  { to: "/admin/users", label: "Users" },
  { to: "/admin/reports", label: "Reports" },
  { to: "/admin/flagged-accounts", label: "Flagged" },
  { to: "/admin/organiser-requests", label: "Organisers" },
  { to: "/admin/events", label: "Events" },
  { to: "/admin/audit-log", label: "Audit log" },
  { to: "/admin/admins", label: "Admins" },
];

const route = useRoute();
const isMobileMenuOpen = ref(false);

watch(() => route.fullPath, () => {
  isMobileMenuOpen.value = false;
});
</script>
