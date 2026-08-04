<template>
  <AuthLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-4">Log in</h1>
    <form @submit.prevent="handleSubmit">
      <div>
        <InputLabel for-id="email" value="Email" />
        <TextInput id="email" v-model="email" type="email" autocomplete="username" required />
      </div>

      <div class="mt-4">
        <InputLabel for-id="password" value="Password" />
        <TextInput id="password" v-model="password" type="password" autocomplete="current-password" required />
      </div>

      <InputError :message="errorMessage" />

      <div class="flex items-center justify-between mt-6">
        <RouterLink to="/register" class="underline text-sm text-gray-600 hover:text-gray-900">
          Need an account?
        </RouterLink>
        <PrimaryButton :disabled="isSubmitting">
          {{ isSubmitting ? "Logging in…" : "Log in" }}
        </PrimaryButton>
      </div>
    </form>
  </AuthLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import InputError from "../components/InputError.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { getApiError } from "../services/apiError.js";

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);

const authStore = useAuthStore();
const router = useRouter();

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push("/dashboard");
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't log you in. Please check your email and password.").message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>
