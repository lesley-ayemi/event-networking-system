<template>
  <DefaultLayout>
    <RouterLink to="/my-events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to my events</RouterLink>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl px-6 py-6 mt-4 max-w-2xl">
      <h1 class="text-lg font-semibold text-gray-900 mb-6">Create an event</h1>
      <EventForm
        :is-submitting="isSubmitting"
        :error-message="errorMessage"
        :submit-label="(busy) => (busy ? 'Creating…' : 'Create event')"
        @submit="handleSubmit"
        @cancel="router.push('/my-events')"
      />
    </div>
  </DefaultLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import EventForm from "../components/EventForm.vue";
import { useEventsStore } from "../stores/eventsStore.js";
import { getApiError } from "../services/apiError.js";

const router = useRouter();
const eventsStore = useEventsStore();

const isSubmitting = ref(false);
const errorMessage = ref("");

async function handleSubmit(payload) {
  isSubmitting.value = true;
  errorMessage.value = "";
  try {
    const event = await eventsStore.createEvent(payload);
    router.push(`/events/${event.id}`);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't create that event. Please check your answers and try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>
