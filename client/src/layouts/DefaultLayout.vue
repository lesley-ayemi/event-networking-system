<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-5xl mx-auto px-6 flex items-center gap-6 h-14 overflow-x-auto">
        <RouterLink to="/dashboard" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Dashboard</RouterLink>
        <RouterLink to="/events" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Events</RouterLink>
        <RouterLink to="/saved-events" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Saved</RouterLink>
        <RouterLink to="/matches" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Matches</RouterLink>
        <RouterLink to="/friends" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Friends</RouterLink>
        <RouterLink to="/messages" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Messages</RouterLink>
        <RouterLink to="/profile" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Profile</RouterLink>
        <RouterLink
          v-if="userStore.user?.is_admin"
          to="/admin/reports"
          class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap"
        >
          Admin
        </RouterLink>
        <NotificationBell class="ms-auto shrink-0" />
      </div>
    </nav>
    <main class="max-w-5xl mx-auto px-6 py-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { RouterLink } from "vue-router";
import NotificationBell from "../components/NotificationBell.vue";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { useUserStore } from "../stores/userStore.js";

// The bell is global chrome shown on every authenticated page, so its data
// is fetched here rather than relying on whichever page happens to be open.
const conversationsStore = useConversationsStore();
const friendsStore = useFriendsStore();
const userStore = useUserStore();

onMounted(() => {
  conversationsStore.fetchConversations();
  friendsStore.fetchAll();
});
</script>
