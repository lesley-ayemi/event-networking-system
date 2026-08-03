<template>
  <div class="bg-white shadow-sm rounded-lg p-5 flex flex-col">
    <div class="flex items-start justify-between gap-2">
      <h3 class="font-semibold text-gray-900">{{ event.name }}</h3>
      <BookmarkButton :event-id="event.id" :is-bookmarked="event.is_bookmarked" />
    </div>

    <p class="text-sm text-gray-500 mt-1">{{ formattedDate }}</p>
    <p class="text-sm text-gray-500">{{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}</p>

    <span
      class="inline-flex w-fit items-center text-xs font-medium px-2 py-0.5 rounded-full mt-3"
      :class="event.is_virtual ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-100 text-gray-600'"
    >
      {{ event.is_virtual ? "Virtual" : "In person" }}
    </span>

    <div v-if="event.one_to_one_available || event.small_group_available" class="flex flex-wrap gap-1 mt-2">
      <span v-if="event.one_to_one_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
        One-to-one
      </span>
      <span v-if="event.small_group_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
        Small group
      </span>
    </div>

    <p class="text-sm text-gray-500 mt-3">{{ event.attendees_count }} attending</p>

    <RouterLink
      :to="`/events/${event.id}`"
      class="mt-4 inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
    >
      View details
    </RouterLink>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { RouterLink } from "vue-router";
import BookmarkButton from "./BookmarkButton.vue";

const props = defineProps({
  event: { type: Object, required: true },
});

const formattedDate = computed(() => {
  if (!props.event.starts_at) {
    return "";
  }
  return new Date(props.event.starts_at).toLocaleString(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  });
});
</script>
