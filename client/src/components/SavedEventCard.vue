<template>
  <div class="bg-white shadow-sm rounded-lg p-4 flex items-start justify-between gap-2">
    <RouterLink :to="`/events/${event.id}`" class="min-w-0 flex-1 hover:opacity-80">
      <p class="font-semibold text-gray-900 text-sm truncate">{{ event.name }}</p>
      <p class="text-xs text-gray-500 mt-1">{{ formattedDate }}</p>
      <p class="text-xs text-gray-500">{{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}</p>
    </RouterLink>
    <BookmarkButton :event-id="event.id" :is-bookmarked="event.is_bookmarked" />
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
  return new Date(props.event.starts_at).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
});
</script>
