<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm sticky top-0 z-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-6 min-w-0">
            <RouterLink to="/dashboard" class="text-base font-bold text-gray-900 tracking-tight shrink-0">
              Event<span class="text-indigo-600">Networking</span>
            </RouterLink>
            <div class="hidden md:flex items-center gap-6">
              <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to" class="text-sm font-medium text-gray-500 hover:text-gray-900 whitespace-nowrap">
                {{ link.label }}
              </RouterLink>
              <RouterLink
                v-if="userStore.user?.is_admin"
                to="/admin/reports"
                class="text-sm font-medium text-gray-500 hover:text-gray-900 whitespace-nowrap"
              >
                Admin
              </RouterLink>
            </div>
          </div>

          <div class="flex items-center gap-3 shrink-0">
            <RouterLink
              v-if="userStore.user?.organiser_status === 'approved'"
              to="/my-events"
              class="hidden sm:inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-semibold hover:bg-indigo-100 whitespace-nowrap"
            >
              + Create event
            </RouterLink>
            <NotificationBell />
            <button
              type="button"
              class="md:hidden text-gray-500 hover:text-gray-900 p-1"
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
        </div>

        <div v-if="isMobileMenuOpen" class="md:hidden pb-4 space-y-1 border-t border-gray-100 pt-3">
          <RouterLink
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900"
          >
            {{ link.label }}
          </RouterLink>
          <RouterLink
            v-if="userStore.user?.is_admin"
            to="/admin/reports"
            class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900"
          >
            Admin
          </RouterLink>
          <RouterLink
            v-if="userStore.user?.organiser_status === 'approved'"
            to="/my-events"
            class="block px-3 py-2 rounded-lg text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100"
          >
            + Create event
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
import { onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import NotificationBell from "../components/NotificationBell.vue";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { useUserStore } from "../stores/userStore.js";

const navLinks = [
  { to: "/dashboard", label: "Dashboard" },
  { to: "/events", label: "Events" },
  { to: "/saved-events", label: "Saved" },
  { to: "/matches", label: "Matches" },
  { to: "/friends", label: "Friends" },
  { to: "/messages", label: "Messages" },
  { to: "/profile", label: "Profile" },
];

// The bell is global chrome shown on every authenticated page, so its data
// is fetched here rather than relying on whichever page happens to be open.
const conversationsStore = useConversationsStore();
const friendsStore = useFriendsStore();
const userStore = useUserStore();
const route = useRoute();
const isMobileMenuOpen = ref(false);

watch(() => route.fullPath, () => {
  isMobileMenuOpen.value = false;
});

onMounted(() => {
  conversationsStore.fetchConversations();
  friendsStore.fetchAll();
});
</script>
