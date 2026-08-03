<template>
  <div class="bg-white shadow-sm rounded-lg p-5 flex flex-col">
    <div class="flex items-start justify-between gap-2">
      <h3 class="font-semibold text-gray-900">{{ event.name }}</h3>
      <button
        type="button"
        disabled
        title="Saving events is coming soon"
        class="shrink-0 text-gray-300 cursor-not-allowed"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 4.5h12a1 1 0 0 1 1 1V21l-7-4-7 4V5.5a1 1 0 0 1 1-1Z" />
        </svg>
      </button>
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
