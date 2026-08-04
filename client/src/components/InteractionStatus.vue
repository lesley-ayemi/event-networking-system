<template>
  <div>
    <div class="flex items-center gap-2">
      <span class="text-xs text-gray-500">Your status:</span>
      <select
        :value="status"
        class="text-xs border-gray-300 rounded-md text-gray-600 focus:ring-indigo-500 focus:border-indigo-500"
        :disabled="isSaving"
        @change="handleChange"
      >
        <option v-for="option in AVAILABILITY_STATUSES" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>
    <p v-if="errorMessage" class="text-xs text-red-600 mt-1">{{ errorMessage }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useUserStore } from "../stores/userStore.js";
import { AVAILABILITY_STATUSES } from "../constants/conversationTools.js";
import { getApiError } from "../services/apiError.js";

defineProps({
  status: { type: String, default: "available" },
});

const userStore = useUserStore();
const isSaving = ref(false);
const errorMessage = ref("");

async function handleChange(event) {
  isSaving.value = true;
  errorMessage.value = "";
  try {
    await userStore.updateProfile({ availability_status: event.target.value });
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't update your status. Please try again.").message;
  } finally {
    isSaving.value = false;
  }
}
</script>
