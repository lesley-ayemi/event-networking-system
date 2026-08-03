<template>
  <DefaultLayout>
    <RouterLink to="/events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to events</RouterLink>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600 mt-4">{{ eventsStore.error }}</p>

    <div v-else-if="event" class="bg-white shadow-md rounded-lg px-6 py-6 mt-4 max-w-2xl">
      <div class="flex items-start justify-between gap-4">
        <h1 class="text-lg font-semibold text-gray-900">{{ event.name }}</h1>
        <span
          class="inline-flex shrink-0 items-center text-xs font-medium px-2 py-0.5 rounded-full"
          :class="event.is_virtual ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-100 text-gray-600'"
        >
          {{ event.is_virtual ? "Virtual" : "In person" }}
        </span>
      </div>

      <dl class="mt-4 space-y-2 text-sm">
        <div class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Date</dt>
          <dd class="text-gray-900">{{ formattedDateRange }}</dd>
        </div>
        <div class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Location</dt>
          <dd class="text-gray-900">{{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}</dd>
        </div>
        <div v-if="event.industry" class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Industry</dt>
          <dd class="text-gray-900">{{ event.industry }}</dd>
        </div>
        <div class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Price</dt>
          <dd class="text-gray-900">{{ event.is_free ? "Free" : `$${event.price}` }}</dd>
        </div>
        <div class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Attendees</dt>
          <dd class="text-gray-900">
            {{ event.attendees_count }}<span v-if="event.capacity"> / {{ event.capacity }}</span>
          </dd>
        </div>
        <div v-if="event.one_to_one_available || event.small_group_available" class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Interaction</dt>
          <dd class="text-gray-900 flex flex-wrap gap-1">
            <span v-if="event.one_to_one_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
              One-to-one
            </span>
            <span v-if="event.small_group_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
              Small group
            </span>
          </dd>
        </div>
        <div v-if="event.accessibility_options?.length" class="flex gap-2">
          <dt class="text-gray-500 w-28 shrink-0">Accessibility</dt>
          <dd class="text-gray-900">{{ accessibilityLabels.join(", ") }}</dd>
        </div>
      </dl>

      <p v-if="event.description" class="text-sm text-gray-700 mt-4">{{ event.description }}</p>

      <p v-if="registrationError" class="text-sm text-red-600 mt-4">{{ registrationError }}</p>

      <div class="mt-6">
        <PrimaryButton v-if="!event.is_registered" :disabled="isSubmitting" @click="handleRegister">
          {{ isSubmitting ? "Registering…" : "Register" }}
        </PrimaryButton>
        <SecondaryButton v-else :disabled="isSubmitting" @click="handleCancel">
          {{ isSubmitting ? "Cancelling…" : "Cancel registration" }}
        </SecondaryButton>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useEventsStore } from "../stores/eventsStore.js";

const ACCESSIBILITY_LABELS = {
  wheelchair_accessible: "Wheelchair accessible",
  asl_interpretation: "ASL interpretation",
  quiet_room: "Quiet room",
  captioning: "Captioning",
};

const route = useRoute();
const eventsStore = useEventsStore();
const isSubmitting = ref(false);
const registrationError = ref("");

const event = computed(() => eventsStore.currentEvent);

const accessibilityLabels = computed(
  () => event.value?.accessibility_options?.map((option) => ACCESSIBILITY_LABELS[option] ?? option) ?? []
);

const formattedDateRange = computed(() => {
  if (!event.value?.starts_at) {
    return "";
  }
  const starts = new Date(event.value.starts_at).toLocaleString(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  });
  if (!event.value.ends_at) {
    return starts;
  }
  const ends = new Date(event.value.ends_at).toLocaleTimeString(undefined, { timeStyle: "short" });
  return `${starts} – ${ends}`;
});

async function handleRegister() {
  registrationError.value = "";
  isSubmitting.value = true;
  try {
    await eventsStore.register(route.params.id);
  } catch (error) {
    registrationError.value = "We couldn't register you for this event. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
}

async function handleCancel() {
  registrationError.value = "";
  isSubmitting.value = true;
  try {
    await eventsStore.cancelRegistration(route.params.id);
  } catch (error) {
    registrationError.value = "We couldn't cancel your registration. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  eventsStore.fetchEvent(route.params.id);
});
</script>
