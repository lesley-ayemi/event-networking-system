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
  </DefaultLayout>
</template>

<script setup>
import { reactive, ref } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Checkbox from "../components/Checkbox.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { AVAILABILITY_STATUSES, CONVERSATION_BOUNDARIES } from "../constants/conversationTools.js";

const authStore = useAuthStore();

const availabilityStatus = ref(authStore.user?.availability_status ?? "available");
const boundaries = reactive(
  Object.fromEntries(
    CONVERSATION_BOUNDARIES.map((boundary) => [
      boundary.key,
      authStore.user?.conversation_boundaries?.[boundary.key] ?? false,
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
    await authStore.updateProfile({
      availability_status: availabilityStatus.value,
      conversation_boundaries: { ...boundaries },
    });
    statusMessage.value = "Saved.";
  } catch (error) {
    errorMessage.value = "We couldn't save your changes. Please try again.";
  } finally {
    isSaving.value = false;
  }
}
</script>
