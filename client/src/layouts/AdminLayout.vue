<template>
  <div class="min-h-screen bg-gray-50 md:flex">
    <!-- Desktop sidebar -->
    <aside class="hidden md:flex md:flex-col md:w-64 md:shrink-0 bg-gray-900 text-gray-300">
      <SidebarContent :nav-groups="navGroups" />
    </aside>

    <!-- Mobile sidebar drawer -->
    <div v-if="isSidebarOpen" class="md:hidden fixed inset-0 z-40 flex">
      <div class="fixed inset-0 bg-black/50" @click="isSidebarOpen = false" />
      <aside class="relative w-64 flex flex-col bg-gray-900 text-gray-300">
        <SidebarContent :nav-groups="navGroups" @navigate="isSidebarOpen = false" />
      </aside>
    </div>

    <div class="flex-1 min-w-0 flex flex-col">
      <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6">
          <div class="flex items-center gap-3 min-w-0">
            <button
              type="button"
              class="md:hidden text-gray-500 hover:text-gray-900 p-1 shrink-0"
              aria-label="Toggle menu"
              @click="isSidebarOpen = true"
            >
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
              </svg>
            </button>
            <h1 class="text-sm font-semibold text-gray-500 truncate">{{ pageTitle }}</h1>
          </div>

          <div class="flex items-center gap-4 shrink-0">
            <NotificationBell />

            <div class="relative">
              <button
                type="button"
                class="flex items-center gap-2"
                aria-haspopup="true"
                :aria-expanded="isProfileMenuOpen"
                @click="isProfileMenuOpen = !isProfileMenuOpen"
              >
                <Avatar v-if="userStore.user" :user="userStore.user" />
                <span class="hidden sm:inline text-sm font-medium text-gray-700">{{ userStore.user?.first_name }}</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
              </button>

              <div
                v-if="isProfileMenuOpen"
                class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-xl ring-1 ring-gray-100 py-1 z-10"
              >
                <RouterLink to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="isProfileMenuOpen = false">
                  Profile
                </RouterLink>
                <RouterLink to="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="isProfileMenuOpen = false">
                  &larr; Back to app
                </RouterLink>
                <button type="button" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50" @click="handleLogout">
                  Log out
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import SidebarContent from "../components/admin/SidebarContent.vue";
import NotificationBell from "../components/NotificationBell.vue";
import Avatar from "../components/Avatar.vue";
import { useAuthStore } from "../stores/authStore.js";
import { useUserStore } from "../stores/userStore.js";
import { ADMIN_NAV_GROUPS, matchesAdminLink } from "../constants/adminNav.js";

const navGroups = ADMIN_NAV_GROUPS;

const authStore = useAuthStore();
const userStore = useUserStore();
const route = useRoute();
const router = useRouter();
const isSidebarOpen = ref(false);
const isProfileMenuOpen = ref(false);

const pageTitle = computed(() => {
  for (const group of navGroups) {
    const match = group.links.find((link) => matchesAdminLink(route.path, link.to));
    if (match) {
      return match.label;
    }
  }
  return "Admin";
});

watch(() => route.fullPath, () => {
  isSidebarOpen.value = false;
  isProfileMenuOpen.value = false;
});

async function handleLogout() {
  try {
    await authStore.logout();
  } catch (error) {
    // logout() clears the local session in its own finally block even when
    // the request fails, so there's nothing left to recover from here.
  }
  router.push("/login");
}
</script>
