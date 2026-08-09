<template>
  <button
    type="button"
    :disabled="isToggling"
    :title="isBookmarked ? 'Remove from saved events' : 'Save this event'"
    :aria-label="isBookmarked ? 'Remove from saved events' : 'Save this event'"
    :aria-pressed="isBookmarked"
    class="shrink-0 text-gray-400 hover:text-indigo-600 disabled:opacity-50"
    @click="handleClick"
  >
    <svg v-if="isBookmarked" class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M6 4.5h12a1 1 0 0 1 1 1V21l-7-4-7 4V5.5a1 1 0 0 1 1-1Z" />
    </svg>
    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 4.5h12a1 1 0 0 1 1 1V21l-7-4-7 4V5.5a1 1 0 0 1 1-1Z" />
    </svg>
  </button>
</template>

<script setup>
import { ref } from "vue";
import { useBookmarkStore } from "../stores/bookmarkStore.js";

const props = defineProps({
  eventId: { type: [Number, String], required: true },
  isBookmarked: { type: Boolean, default: false },
});

const bookmarkStore = useBookmarkStore();
const isToggling = ref(false);

async function handleClick() {
  isToggling.value = true;
  try {
    await bookmarkStore.toggleBookmark(props.eventId, props.isBookmarked);
  } catch (error) {
    // is_bookmarked simply won't flip on failure, which is signal enough here
  } finally {
    isToggling.value = false;
  }
}
</script>
