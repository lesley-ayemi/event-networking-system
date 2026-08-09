<template>
  <DefaultLayout>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <h1 class="text-lg font-medium text-gray-900">My events</h1>
      <RouterLink
        to="/events/new"
        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition"
      >
        + Create event
      </RouterLink>
    </div>

    <p v-if="eventsStore.isLoadingOrganizedEvents" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="eventsStore.organizedEventsError" class="text-sm text-red-600">{{ eventsStore.organizedEventsError }}</p>
    <p v-else-if="eventsStore.organizedEvents.length === 0" class="text-sm text-gray-500">
      You haven't created any events yet.
    </p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="event in eventsStore.organizedEvents" :key="event.id" class="p-4 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate">{{ event.name }}</p>
          <p class="text-xs text-gray-500">
            {{ formattedDate(event.starts_at) }} · {{ event.attendees_count }} attending
          </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <RouterLink :to="`/events/${event.id}`" class="text-xs text-gray-500 hover:text-gray-900">View</RouterLink>
          <RouterLink
            :to="`/events/${event.id}/edit`"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
          >
            Edit
          </RouterLink>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { onMounted } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import { useEventsStore } from "../stores/eventsStore.js";

const eventsStore = useEventsStore();

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

onMounted(() => {
  eventsStore.fetchOrganizedEvents();
});
</script>
