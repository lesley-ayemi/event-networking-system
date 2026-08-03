<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Saved events</h1>

    <p v-if="bookmarkStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="bookmarkStore.error" class="text-sm text-red-600">{{ bookmarkStore.error }}</p>
    <p v-else-if="bookmarkStore.bookmarkedEvents.length === 0" class="text-sm text-gray-500">
      You haven't saved any events yet. Browse
      <RouterLink to="/events" class="underline text-gray-700 hover:text-gray-900">events</RouterLink>
      and tap the bookmark icon to save one here.
    </p>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <SavedEventCard v-for="event in bookmarkStore.bookmarkedEvents" :key="event.id" :event="event" />
    </div>
  </DefaultLayout>
</template>

<script setup>
import { onMounted } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import SavedEventCard from "../components/SavedEventCard.vue";
import { useBookmarkStore } from "../stores/bookmarkStore.js";

const bookmarkStore = useBookmarkStore();

onMounted(() => {
  bookmarkStore.fetchBookmarks();
});
</script>
