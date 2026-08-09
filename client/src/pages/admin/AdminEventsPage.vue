<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Events</h1>

    <div class="flex flex-wrap items-center gap-3 mb-4">
      <TextInput v-model="search" type="search" placeholder="Search by event name" class="w-full sm:w-64" @keyup.enter="applyFilters" />
      <SecondaryButton @click="applyFilters">Search</SecondaryButton>
    </div>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600">{{ eventsStore.error }}</p>
    <p v-else-if="eventsStore.events.length === 0" class="text-sm text-gray-500">No events found.</p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="event in eventsStore.events" :key="event.id" class="p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ event.name }}</p>
          <p class="text-xs text-gray-500 break-words">
            {{ formattedDate(event.starts_at) }} · {{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}
          </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <RouterLink :to="`/admin/events/${event.id}/edit`" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            Edit
          </RouterLink>
          <SecondaryButton :disabled="isBusy(event.id)" @click="remove(event.id)">Remove</SecondaryButton>
        </div>
      </div>
    </div>

    <div
      v-if="eventsStore.pagination && eventsStore.pagination.last_page > 1"
      class="flex flex-wrap items-center justify-between gap-3 mt-6"
    >
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ eventsStore.pagination.current_page }} of {{ eventsStore.pagination.last_page }}
      </span>
      <SecondaryButton :disabled="currentPage === eventsStore.pagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </SecondaryButton>
    </div>

    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import TextInput from "../../components/TextInput.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useEventsStore } from "../../stores/eventsStore.js";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const eventsStore = useEventsStore();
const adminStore = useAdminStore();
const busyEventIds = reactive(new Set());
const actionError = ref("");
const currentPage = ref(1);
const search = ref("");

function isBusy(eventId) {
  return busyEventIds.has(eventId);
}

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

function load() {
  eventsStore.fetchEvents({ search: search.value || undefined, page: currentPage.value });
}

function applyFilters() {
  currentPage.value = 1;
  load();
}

function goToPage(page) {
  currentPage.value = page;
  load();
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

onMounted(load);
</script>
