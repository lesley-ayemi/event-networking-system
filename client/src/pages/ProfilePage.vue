<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Profile</h1>

    <div class="bg-white shadow-sm rounded-lg p-6 max-w-xl">
      <h2 class="text-base font-semibold text-gray-900 mb-1">Availability status</h2>
      <p class="text-sm text-gray-500 mb-4">Let people know how open you are to chatting right now.</p>

      <div class="space-y-3">
        <label v-for="status in AVAILABILITY_STATUSES" :key="status.value" class="flex items-center">
          <input
            type="radio"
            name="availability_status"
            :value="status.value"
            v-model="availabilityStatus"
            class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
          />
          <span class="ms-2 text-sm text-gray-700">{{ status.label }}</span>
        </label>
      </div>

      <h2 class="text-base font-semibold text-gray-900 mt-8 mb-1">Conversation boundaries</h2>
      <p class="text-sm text-gray-500 mb-4">Set expectations so conversations stay comfortable for you.</p>

      <div class="space-y-3">
        <label v-for="boundary in CONVERSATION_BOUNDARIES" :key="boundary.key" class="flex items-center">
          <Checkbox v-model="boundaries[boundary.key]" />
          <span class="ms-2 text-sm text-gray-700">{{ boundary.label }}</span>
        </label>
      </div>

      <p v-if="statusMessage" class="text-sm text-gray-500 mt-6">{{ statusMessage }}</p>
      <p v-if="errorMessage" class="text-sm text-red-600 mt-6">{{ errorMessage }}</p>

      <div class="mt-6">
        <PrimaryButton :disabled="isSaving" @click="save">{{ isSaving ? "Saving…" : "Save" }}</PrimaryButton>
      </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6 max-w-xl mt-6">
      <h2 class="text-base font-semibold text-gray-900 mb-1">Event organiser</h2>
      <p class="text-sm text-gray-500 mb-4">Approved organisers can create and publish events.</p>

      <p v-if="organiserStatus === 'approved'" class="text-sm text-green-700">
        You're an approved organiser.
      </p>
      <p v-else-if="organiserStatus === 'pending'" class="text-sm text-gray-600">
        Your request is pending review.
      </p>
      <template v-else>
        <p v-if="organiserStatus === 'rejected'" class="text-sm text-gray-600 mb-3">
          Your previous request wasn't approved. You can request again.
        </p>
        <SecondaryButton :disabled="isRequestingOrganiser" @click="requestOrganiser">
          {{ isRequestingOrganiser ? "Requesting…" : "Request organiser access" }}
        </SecondaryButton>
      </template>
      <p v-if="organiserError" class="text-sm text-red-600 mt-3">{{ organiserError }}</p>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Checkbox from "../components/Checkbox.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useUserStore } from "../stores/userStore.js";
import { AVAILABILITY_STATUSES, CONVERSATION_BOUNDARIES } from "../constants/conversationTools.js";
import { getApiError } from "../services/apiError.js";

const userStore = useUserStore();

const availabilityStatus = ref(userStore.user?.availability_status ?? "available");
const boundaries = reactive(
  Object.fromEntries(
    CONVERSATION_BOUNDARIES.map((boundary) => [
      boundary.key,
      userStore.user?.conversation_boundaries?.[boundary.key] ?? false,
    ])
  )
);

const isSaving = ref(false);
const statusMessage = ref("");
const errorMessage = ref("");

async function save() {
  isSaving.value = true;
  statusMessage.value = "";
  errorMessage.value = "";
  try {
    await userStore.updateProfile({
      availability_status: availabilityStatus.value,
      conversation_boundaries: { ...boundaries },
    });
    statusMessage.value = "Saved.";
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't save your changes. Please try again.").message;
  } finally {
    isSaving.value = false;
  }
}

const organiserStatus = computed(() => userStore.user?.organiser_status ?? "none");
const isRequestingOrganiser = ref(false);
const organiserError = ref("");

async function requestOrganiser() {
  isRequestingOrganiser.value = true;
  organiserError.value = "";
  try {
    await userStore.requestOrganiserStatus();
  } catch (error) {
    organiserError.value = getApiError(error, "We couldn't submit that request. Please try again.").message;
  } finally {
    isRequestingOrganiser.value = false;
  }
}
</script>
