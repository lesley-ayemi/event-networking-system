<template>
  <DefaultLayout>
    <RouterLink to="/events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to events</RouterLink>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600 mt-4">{{ eventsStore.error }}</p>

    <div v-else-if="event" class="bg-white shadow-md ring-1 ring-gray-100 rounded-xl overflow-hidden mt-4 max-w-2xl">
      <img v-if="event.cover_image" :src="event.cover_image" alt="" class="w-full h-48 object-cover" />

      <div class="px-5 sm:px-6 py-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <h1 class="text-lg font-semibold text-gray-900">{{ event.name }}</h1>
        <div class="flex items-center gap-3 shrink-0">
          <span
            class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full"
            :class="event.is_virtual ? 'bg-indigo-50 text-indigo-600' : 'bg-gray-100 text-gray-600'"
          >
            {{ event.is_virtual ? "Virtual" : "In person" }}
          </span>
          <BookmarkButton :event-id="event.id" :is-bookmarked="event.is_bookmarked" />
        </div>
      </div>

      <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
        <div class="flex items-start gap-2.5">
          <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
          </svg>
          <div>
            <dt class="text-xs text-gray-500">Date</dt>
            <dd class="text-gray-900 font-medium">{{ formattedDateRange }}</dd>
          </div>
        </div>
        <div class="flex items-start gap-2.5">
          <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
          </svg>
          <div>
            <dt class="text-xs text-gray-500">Location</dt>
            <dd class="text-gray-900 font-medium">{{ event.is_virtual ? "Virtual" : event.location || "Location TBA" }}</dd>
          </div>
        </div>
        <div v-if="event.industry" class="flex items-start gap-2.5">
          <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
          </svg>
          <div>
            <dt class="text-xs text-gray-500">Industry</dt>
            <dd class="text-gray-900 font-medium">{{ event.industry }}</dd>
          </div>
        </div>
        <div class="flex items-start gap-2.5">
          <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
          </svg>
          <div>
            <dt class="text-xs text-gray-500">Price</dt>
            <dd class="text-gray-900 font-medium">{{ event.is_free ? "Free" : `$${event.price}` }}</dd>
          </div>
        </div>
      </dl>

      <div class="mt-4 flex items-start gap-2.5 text-sm">
        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
        </svg>
        <div class="flex-1 min-w-0">
          <div class="flex items-baseline justify-between gap-2">
            <dt class="text-xs text-gray-500">Attendees</dt>
            <dd class="text-gray-900 font-medium shrink-0">
              {{ event.attendees_count }}<span v-if="event.capacity"> / {{ event.capacity }}</span>
            </dd>
          </div>
          <div v-if="event.capacity" class="w-full h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
            <div class="h-full bg-indigo-600 rounded-full" :style="{ width: `${capacityPercent}%` }" />
          </div>
        </div>
      </div>

      <div v-if="event.one_to_one_available || event.small_group_available" class="flex flex-wrap gap-1 mt-4">
        <span v-if="event.one_to_one_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
          One-to-one
        </span>
        <span v-if="event.small_group_available" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
          Small group
        </span>
      </div>
      <p v-if="event.accessibility_options?.length" class="text-xs text-gray-500 mt-2">
        Accessibility: {{ accessibilityLabels.join(", ") }}
      </p>

      <template v-if="event.description">
        <h2 class="text-sm font-semibold text-gray-900 mt-5">About this event</h2>
        <p class="text-sm text-gray-700 mt-1">{{ event.description }}</p>
      </template>

      <!-- Not registered: either a plain Register button, or the matching-question form -->
      <div v-if="!event.is_registered" class="mt-6">
        <PrimaryButton v-if="!showForm" @click="openForm">Register</PrimaryButton>

        <form v-else @submit.prevent="handleRegister" class="border-t border-gray-100 pt-5 space-y-5">
          <p class="text-sm text-gray-600">A few quick questions — these help us match you well.</p>

          <div>
            <InputLabel value="Which interaction mode do you prefer?" />
            <div class="mt-1 space-y-1">
              <label v-for="option in interactionModeOptions" :key="option.value" class="flex items-center text-sm text-gray-700">
                <input
                  type="radio"
                  v-model="answers.interaction_mode"
                  :value="option.value"
                  class="text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ms-2">{{ option.label }}</span>
              </label>
            </div>
          </div>

          <label class="flex items-center">
            <Checkbox v-model="answers.open_to_matching" />
            <span class="ms-2 text-sm text-gray-700">Are you open to being matched?</span>
          </label>

          <label class="flex items-center">
            <Checkbox v-model="answers.message_before_event" />
            <span class="ms-2 text-sm text-gray-700">Would you like to message your match before the event?</span>
          </label>

          <div>
            <InputLabel for-id="preferred_group_size" value="What group size feels comfortable?" />
            <TextInput
              id="preferred_group_size"
              v-model.number="answers.preferred_group_size"
              type="number"
              min="2"
              max="50"
              class="sm:w-40"
            />
          </div>

          <div>
            <InputLabel value="Are you attending virtually or physically?" />
            <div class="mt-1 flex gap-4">
              <label class="flex items-center text-sm text-gray-700">
                <input
                  type="radio"
                  v-model="answers.attendance_format"
                  value="virtual"
                  class="text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ms-2">Virtual</span>
              </label>
              <label class="flex items-center text-sm text-gray-700">
                <input
                  type="radio"
                  v-model="answers.attendance_format"
                  value="physical"
                  class="text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ms-2">Physical</span>
              </label>
            </div>
          </div>

          <InputError :message="registrationError" />

          <div class="flex items-center gap-3">
            <SecondaryButton type="button" :disabled="isSubmitting" @click="showForm = false">Cancel</SecondaryButton>
            <PrimaryButton :disabled="isSubmitting">
              {{ isSubmitting ? "Registering…" : "Confirm registration" }}
            </PrimaryButton>
          </div>
        </form>
      </div>

      <!-- Registered: show what was submitted -->
      <div v-else class="mt-6 border-t border-gray-100 pt-5">
        <div class="bg-indigo-50 ring-1 ring-indigo-100 rounded-xl p-4">
          <p class="flex items-center gap-1.5 font-medium text-indigo-900 text-sm mb-3">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            You're registered
          </p>
          <dl class="text-sm text-indigo-900/80 space-y-1">
            <div class="flex gap-2">
              <dt class="w-40 shrink-0">Interaction mode</dt>
              <dd>{{ interactionModeLabel }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="w-40 shrink-0">Attending</dt>
              <dd>{{ event.my_registration?.attendance_format === "virtual" ? "Virtual" : "Physical" }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="w-40 shrink-0">Open to matching</dt>
              <dd>{{ event.my_registration?.open_to_matching ? "Yes" : "No" }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="w-40 shrink-0">Message before event</dt>
              <dd>{{ event.my_registration?.message_before_event ? "Yes" : "No" }}</dd>
            </div>
            <div class="flex gap-2">
              <dt class="w-40 shrink-0">Comfortable group size</dt>
              <dd>{{ event.my_registration?.preferred_group_size }}</dd>
            </div>
          </dl>
        </div>

        <p v-if="registrationError" class="text-sm text-red-600 mt-4">{{ registrationError }}</p>

        <SecondaryButton class="mt-4" :disabled="isSubmitting" @click="handleCancel">
          {{ isSubmitting ? "Cancelling…" : "Cancel registration" }}
        </SecondaryButton>
      </div>

      <button
        type="button"
        class="text-xs text-gray-500 hover:text-gray-600 underline mt-6"
        @click="showReportModal = true"
      >
        Report this event
      </button>
      </div>
    </div>

    <ReportModal
      v-if="showReportModal && event"
      :title="`Report ${event.name}`"
      reportable-type="event"
      :reportable-id="event.id"
      @close="showReportModal = false"
    />
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import InputError from "../components/InputError.vue";
import Checkbox from "../components/Checkbox.vue";
import BookmarkButton from "../components/BookmarkButton.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import ReportModal from "../components/ReportModal.vue";
import { useUserStore } from "../stores/userStore.js";
import { useEventsStore } from "../stores/eventsStore.js";
import { getApiError } from "../services/apiError.js";

const ACCESSIBILITY_LABELS = {
  wheelchair_accessible: "Wheelchair accessible",
  asl_interpretation: "ASL interpretation",
  quiet_room: "Quiet room",
  captioning: "Captioning",
};

const INTERACTION_MODE_LABELS = {
  one_to_one: "One-to-one",
  small_group: "Small group",
  either: "No preference",
};

const route = useRoute();
const userStore = useUserStore();
const eventsStore = useEventsStore();
const showReportModal = ref(false);
const isSubmitting = ref(false);
const registrationError = ref("");
const showForm = ref(false);

const answers = reactive({
  interaction_mode: "either",
  open_to_matching: true,
  message_before_event: false,
  preferred_group_size: 4,
  attendance_format: "physical",
});

const event = computed(() => eventsStore.currentEvent);

const interactionModeOptions = computed(() => {
  const options = [];
  if (event.value?.one_to_one_available) {
    options.push({ value: "one_to_one", label: "One-to-one" });
  }
  if (event.value?.small_group_available) {
    options.push({ value: "small_group", label: "Small group" });
  }
  options.push({ value: "either", label: "No preference" });
  return options;
});

const interactionModeLabel = computed(
  () => INTERACTION_MODE_LABELS[event.value?.my_registration?.interaction_mode] ?? "—"
);

const accessibilityLabels = computed(
  () => event.value?.accessibility_options?.map((option) => ACCESSIBILITY_LABELS[option] ?? option) ?? []
);

const capacityPercent = computed(() => {
  if (!event.value?.capacity) {
    return 0;
  }
  return Math.min(100, Math.round((event.value.attendees_count / event.value.capacity) * 100));
});

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

function openForm() {
  const user = userStore.user;
  const prefersOneToOne = event.value?.one_to_one_available && user?.interaction_preferences?.one_to_one;
  const prefersSmallGroup = event.value?.small_group_available && user?.interaction_preferences?.small_groups;

  Object.assign(answers, {
    interaction_mode: prefersOneToOne ? "one_to_one" : prefersSmallGroup ? "small_group" : "either",
    open_to_matching: user?.comfort_settings?.auto_matching ?? true,
    message_before_event: user?.interaction_preferences?.meet_before_event ?? false,
    preferred_group_size: user?.comfort_settings?.max_group_size ?? 4,
    attendance_format: event.value?.is_virtual ? "virtual" : "physical",
  });
  registrationError.value = "";
  showForm.value = true;
}

async function handleRegister() {
  registrationError.value = "";
  isSubmitting.value = true;
  try {
    await eventsStore.register(route.params.id, { ...answers });
    showForm.value = false;
  } catch (error) {
    registrationError.value = getApiError(
      error,
      "We couldn't register you for this event. Please check your answers and try again."
    ).message;
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
    registrationError.value = getApiError(error, "We couldn't cancel your registration. Please try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  eventsStore.fetchEvent(route.params.id);
});
</script>
