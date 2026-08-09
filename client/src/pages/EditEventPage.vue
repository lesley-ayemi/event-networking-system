<template>
  <DefaultLayout>
    <RouterLink to="/my-events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to my events</RouterLink>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="!canEdit" class="text-sm text-red-600 mt-4">You can only edit events you created.</p>

    <div v-else-if="event" class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl px-6 py-6 mt-4 max-w-2xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-lg font-semibold text-gray-900">Edit event</h1>
        <button type="button" class="text-xs text-red-600 hover:text-red-700" @click="handleDelete">
          Delete event
        </button>
      </div>
      <EventForm
        :event="event"
        :is-submitting="isSubmitting"
        :error-message="errorMessage"
        @submit="handleSubmit"
        @cancel="router.push('/my-events')"
      />
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import EventForm from "../components/EventForm.vue";
import { useEventsStore } from "../stores/eventsStore.js";
import { useUserStore } from "../stores/userStore.js";
import { getApiError } from "../services/apiError.js";

const route = useRoute();
const router = useRouter();
const eventsStore = useEventsStore();
const userStore = useUserStore();

const isSubmitting = ref(false);
const errorMessage = ref("");

const event = computed(() => eventsStore.currentEvent);
const canEdit = computed(() => !event.value || event.value.created_by === userStore.user?.id);

async function handleSubmit(payload) {
  isSubmitting.value = true;
  errorMessage.value = "";
  try {
    await eventsStore.updateEvent(route.params.id, payload);
    router.push(`/events/${route.params.id}`);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't save those changes. Please try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}

async function handleDelete() {
  if (!window.confirm("Delete this event? This cannot be undone.")) {
    return;
  }
  errorMessage.value = "";
  try {
    await eventsStore.deleteEvent(route.params.id);
    router.push("/my-events");
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't delete that event. Please try again.").message;
  }
}

onMounted(() => {
  eventsStore.fetchEvent(route.params.id);
});
</script>
