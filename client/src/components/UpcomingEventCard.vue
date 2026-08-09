<template>
  <RouterLink :to="`/events/${event.id}`" class="block bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-4 hover:bg-gray-50">
    <p class="font-semibold text-gray-900 text-sm">{{ event.name }}</p>
    <p class="text-xs text-gray-500 mt-1">{{ formattedDate }}</p>
    <p class="text-xs text-gray-500">{{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}</p>
  </RouterLink>
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
  return new Date(props.event.starts_at).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
});
</script>
