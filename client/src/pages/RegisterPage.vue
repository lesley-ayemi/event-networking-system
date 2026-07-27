<template>
  <AuthLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-4">Create your account</h1>
    <form @submit.prevent="handleSubmit">
      <div>
        <InputLabel for-id="first_name" value="First name" />
        <TextInput id="first_name" v-model="firstName" type="text" autocomplete="given-name" required />
      </div>

      <div class="mt-4">
        <InputLabel for-id="last_name" value="Last name" />
        <TextInput id="last_name" v-model="lastName" type="text" autocomplete="family-name" required />
      </div>

      <div class="mt-4">
        <InputLabel for-id="email" value="Email" />
        <TextInput id="email" v-model="email" type="email" autocomplete="username" required />
      </div>

      <div class="mt-4">
        <InputLabel for-id="password" value="Password" />
        <TextInput id="password" v-model="password" type="password" minlength="8" autocomplete="new-password" required />
      </div>

      <div class="mt-4">
        <InputLabel for-id="password_confirmation" value="Confirm password" />
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

      <div class="flex items-center justify-between mt-6">
        <RouterLink to="/login" class="underline text-sm text-gray-600 hover:text-gray-900">
          Already registered?
        </RouterLink>
        <PrimaryButton :disabled="isSubmitting">
          {{ isSubmitting ? "Creating account…" : "Create account" }}
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

const firstName = ref("");
const lastName = ref("");
const email = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);

const authStore = useAuthStore();
const router = useRouter();

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.register({
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    router.push("/onboarding");
  } catch (error) {
    errorMessage.value =
      "We couldn't create your account. Your information has not been lost. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>
