<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Events</h1>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600">{{ eventsStore.error }}</p>
    <p v-else-if="eventsStore.events.length === 0" class="text-sm text-gray-500">No events found.</p>

    <div v-else class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
      <div v-for="event in eventsStore.events" :key="event.id" class="p-4 flex items-center justify-between gap-3">
        <div>
          <p class="text-sm font-medium text-gray-900">{{ event.name }}</p>
          <p class="text-xs text-gray-500">
            {{ formattedDate(event.starts_at) }} · {{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}
          </p>
        </div>
        <SecondaryButton :disabled="isBusy(event.id)" @click="remove(event.id)">Remove</SecondaryButton>
      </div>
    </div>
    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useEventsStore } from "../../stores/eventsStore.js";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const eventsStore = useEventsStore();
const adminStore = useAdminStore();
const busyEventIds = reactive(new Set());
const actionError = ref("");

function isBusy(eventId) {
  return busyEventIds.has(eventId);
}

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

async function remove(eventId) {
  busyEventIds.add(eventId);
  actionError.value = "";
  try {
    await adminStore.removeEvent(eventId);
    eventsStore.events = eventsStore.events.filter((event) => event.id !== eventId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't remove that event. Please try again.").message;
  } finally {
    busyEventIds.delete(eventId);
  }
}

onMounted(() => {
  eventsStore.fetchEvents();
});
</script>
