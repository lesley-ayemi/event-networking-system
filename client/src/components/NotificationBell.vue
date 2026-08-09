<template>
  <div class="relative">
    <button
      type="button"
      class="relative text-gray-500 hover:text-gray-900"
      :aria-label="notificationStore.totalCount > 0 ? `Notifications, ${notificationStore.totalCount} unread` : 'Notifications'"
      aria-haspopup="true"
      :aria-expanded="isOpen"
      @click="isOpen = !isOpen"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
        />
      </svg>
      <span
        v-if="notificationStore.totalCount > 0"
        aria-hidden="true"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full bg-indigo-600 text-white text-[10px] font-medium"
      >
        {{ notificationStore.totalCount }}
      </span>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-64 bg-white shadow-lg rounded-lg border border-gray-100 py-2 z-10"
    >
      <p v-if="notificationStore.items.length === 0" class="text-sm text-gray-500 px-4 py-2">You're all caught up.</p>
      <RouterLink
        v-for="item in notificationStore.items"
        :key="item.id"
        :to="item.to"
        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
        @click="isOpen = false"
      >
        {{ item.label }}
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import { useNotificationStore } from "../stores/notificationStore.js";

const notificationStore = useNotificationStore();
const isOpen = ref(false);
</script>
