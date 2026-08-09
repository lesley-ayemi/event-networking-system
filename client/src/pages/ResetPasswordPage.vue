<template>
  <AuthLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-1">Reset your password</h1>

    <template v-if="!missingLinkData">
      <template v-if="!submitted">
        <p class="text-sm text-gray-500 mb-4">Choose a new password for {{ email }}.</p>

        <form @submit.prevent="handleSubmit">
          <div>
            <InputLabel for-id="password" value="New password" />
            <TextInput id="password" v-model="password" type="password" minlength="8" autocomplete="new-password" required />
          </div>

          <div class="mt-4">
            <InputLabel for-id="password_confirmation" value="Confirm new password" />
            <TextInput
              id="password_confirmation"
              v-model="passwordConfirmation"
              type="password"
              minlength="8"
              autocomplete="new-password"
              required
            />
          </div>

          <InputError :message="errorMessage" />

          <div class="flex items-center justify-end mt-6">
            <PrimaryButton :disabled="isSubmitting">
              {{ isSubmitting ? "Resetting…" : "Reset password" }}
            </PrimaryButton>
          </div>
        </form>
      </template>

      <template v-else>
        <p class="text-sm text-gray-700 mb-4">Your password has been reset. You can now log in.</p>
        <div class="flex items-center justify-end">
          <RouterLink to="/login">
            <PrimaryButton type="button">Go to login</PrimaryButton>
          </RouterLink>
        </div>
      </template>
    </template>

    <template v-else>
      <p class="text-sm text-red-600 mb-4">
        This password reset link is missing or incomplete. Please request a new one.
      </p>
      <RouterLink to="/forgot-password" class="underline text-sm text-gray-600 hover:text-gray-900">
        Request a new link
      </RouterLink>
    </template>
  </AuthLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import InputError from "../components/InputError.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { getApiError } from "../services/apiError.js";

const route = useRoute();
const authStore = useAuthStore();

const token = computed(() => route.query.token ?? "");
const email = computed(() => route.query.email ?? "");
const missingLinkData = computed(() => !token.value || !email.value);

const password = ref("");
const passwordConfirmation = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);
const submitted = ref(false);

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    submitted.value = true;
  } catch (error) {
    errorMessage.value = getApiError(
      error,
      "We couldn't reset your password. The link may have expired — please request a new one."
    ).message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>
