<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Dashboard</h1>

    <section>
      <h2 class="text-base font-semibold text-gray-900 mb-4">Saved events</h2>

      <p v-if="eventsStore.isLoadingBookmarks" class="text-sm text-gray-500">Loading…</p>
      <p v-else-if="eventsStore.bookmarksError" class="text-sm text-red-600">{{ eventsStore.bookmarksError }}</p>
      <p v-else-if="eventsStore.bookmarkedEvents.length === 0" class="text-sm text-gray-500">
        You haven't saved any events yet. Browse
        <RouterLink to="/events" class="underline text-gray-700 hover:text-gray-900">events</RouterLink>
        and tap the bookmark icon to save one here.
      </p>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <EventCard v-for="event in eventsStore.bookmarkedEvents" :key="event.id" :event="event" />
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { onMounted } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import EventCard from "../components/EventCard.vue";
import { useEventsStore } from "../stores/eventsStore.js";

const eventsStore = useEventsStore();

onMounted(() => {
  eventsStore.fetchBookmarks();
});
</script>
