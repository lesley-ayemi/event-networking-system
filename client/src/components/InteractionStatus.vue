<template>
  <div>
    <div class="flex items-center gap-2">
      <span class="text-xs" :class="dark ? 'text-indigo-100' : 'text-gray-500'">Your status:</span>
      <Select
        :model-value="status"
        :dark="dark"
        class="text-xs"
        :disabled="isSaving"
        @update:model-value="handleChange"
      >
        <option v-for="option in AVAILABILITY_STATUSES" :key="option.value" :value="option.value" class="text-gray-900">
          {{ option.label }}
        </option>
      </Select>
    </div>
    <p v-if="errorMessage" class="text-xs mt-1" :class="dark ? 'text-orange-200' : 'text-red-600'">{{ errorMessage }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";
import Select from "./Select.vue";
import { useUserStore } from "../stores/userStore.js";
import { AVAILABILITY_STATUSES } from "../constants/conversationTools.js";
import { getApiError } from "../services/apiError.js";

defineProps({
  status: { type: String, default: "available" },
  dark: { type: Boolean, default: false },
});

const userStore = useUserStore();
const isSaving = ref(false);
const errorMessage = ref("");

async function handleChange(value) {
  isSaving.value = true;
  errorMessage.value = "";
  try {
    await userStore.updateProfile({ availability_status: value });
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't update your status. Please try again.").message;
  } finally {
    isSaving.value = false;
  }
}
</script>
