<template>
  <DefaultLayout>
    <RouterLink to="/events" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to events</RouterLink>

    <p v-if="eventsStore.isLoading" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="eventsStore.error" class="text-sm text-red-600 mt-4">{{ eventsStore.error }}</p>

    <div v-else-if="event" class="bg-white shadow-md rounded-lg px-6 py-6 mt-4 max-w-2xl">
      <div class="flex items-start justify-between gap-4">
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
        <p class="font-medium text-gray-900 text-sm mb-2">You're registered</p>
        <dl class="text-sm text-gray-600 space-y-1 mb-4">
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

        <p v-if="registrationError" class="text-sm text-red-600 mb-4">{{ registrationError }}</p>

        <SecondaryButton :disabled="isSubmitting" @click="handleCancel">
          {{ isSubmitting ? "Cancelling…" : "Cancel registration" }}
        </SecondaryButton>
      </div>
    </div>
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
import { useAuthStore } from "../stores/authStore.js";
import { useEventsStore } from "../stores/eventsStore.js";

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
const authStore = useAuthStore();
const eventsStore = useEventsStore();
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
  const user = authStore.user;
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
    registrationError.value = "We couldn't register you for this event. Please check your answers and try again.";
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
