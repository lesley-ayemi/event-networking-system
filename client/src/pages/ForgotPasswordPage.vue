<template>
  <AuthLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-1">Forgot your password?</h1>
    <p class="text-sm text-gray-500 mb-4">
      Enter your email and, if an account exists, we'll send you a link to reset your password.
    </p>

    <template v-if="!submitted">
      <form @submit.prevent="handleSubmit">
        <div>
          <InputLabel for-id="email" value="Email" />
          <TextInput id="email" v-model="email" type="email" autocomplete="username" required />
        </div>

        <InputError :message="errorMessage" />

        <div class="flex items-center justify-between mt-6">
          <RouterLink to="/login" class="underline text-sm text-gray-600 hover:text-gray-900">
            Back to login
          </RouterLink>
          <PrimaryButton :disabled="isSubmitting">
            {{ isSubmitting ? "Sending…" : "Send reset link" }}
          </PrimaryButton>
        </div>
      </form>
    </template>

    <template v-else>
      <p class="text-sm text-gray-700">
        If an account exists for <strong>{{ email }}</strong>, we've sent a link to reset your password.
      </p>
      <div class="flex items-center justify-end mt-6">
        <RouterLink to="/login" class="underline text-sm text-gray-600 hover:text-gray-900">
          Back to login
        </RouterLink>
      </div>
    </template>
  </AuthLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import InputError from "../components/InputError.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { getApiError } from "../services/apiError.js";

const email = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);
const submitted = ref(false);

const authStore = useAuthStore();

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.forgotPassword(email.value);
    submitted.value = true;
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't send that reset link. Please try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>
