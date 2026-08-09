<template>
  <AdminLayout>
    <RouterLink to="/admin/events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to events</RouterLink>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600 mt-4">{{ eventsStore.error }}</p>

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
        @cancel="router.push('/admin/events')"
      />
    </div>

    <div v-if="event" class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl px-6 py-6 mt-6 max-w-2xl">
      <h2 class="text-base font-semibold text-gray-900 mb-4">Attendees</h2>

      <p v-if="adminStore.isLoadingEventRegistrations" class="text-sm text-gray-500">Loading…</p>
      <p v-else-if="adminStore.eventRegistrationsError" class="text-sm text-red-600">
        {{ adminStore.eventRegistrationsError }}
      </p>
      <p v-else-if="adminStore.eventRegistrations.length === 0" class="text-sm text-gray-500">
        No one has registered for this event yet.
      </p>

      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="registration in adminStore.eventRegistrations"
          :key="registration.id"
          class="py-3 flex items-center justify-between gap-3"
        >
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900">
              {{ registration.user?.first_name }} {{ registration.user?.last_name }}
            </p>
            <p class="text-xs text-gray-500">{{ registration.user?.email }}</p>
          </div>
          <SecondaryButton :disabled="isBusyRegistration(registration.id)" @click="removeRegistration(registration.id)">
            Remove
          </SecondaryButton>
        </div>
      </div>

      <p v-if="registrationActionError" class="text-sm text-red-600 mt-4">{{ registrationActionError }}</p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import EventForm from "../../components/EventForm.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useEventsStore } from "../../stores/eventsStore.js";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const route = useRoute();
const router = useRouter();
const eventsStore = useEventsStore();
const adminStore = useAdminStore();

const isSubmitting = ref(false);
const errorMessage = ref("");

const event = computed(() => eventsStore.currentEvent);

const busyRegistrationIds = reactive(new Set());
const registrationActionError = ref("");

function isBusyRegistration(registrationId) {
  return busyRegistrationIds.has(registrationId);
}

async function removeRegistration(registrationId) {
  busyRegistrationIds.add(registrationId);
  registrationActionError.value = "";
  try {
    await adminStore.removeEventRegistration(route.params.id, registrationId);
  } catch (error) {
    registrationActionError.value = getApiError(
      error,
      "We couldn't remove that registration. Please try again."
    ).message;
  } finally {
    busyRegistrationIds.delete(registrationId);
  }
}

async function handleSubmit(payload) {
  isSubmitting.value = true;
  errorMessage.value = "";
  try {
    await adminStore.updateEvent(route.params.id, payload);
    router.push("/admin/events");
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
    await adminStore.removeEvent(route.params.id);
    router.push("/admin/events");
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't delete that event. Please try again.").message;
  }
}

onMounted(() => {
  eventsStore.fetchEvent(route.params.id);
  adminStore.fetchEventRegistrations(route.params.id);
});
</script>
