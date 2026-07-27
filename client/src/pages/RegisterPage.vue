<template>
  <AuthLayout>
    <section>
      <h1>Create your account</h1>
      <form @submit.prevent="handleSubmit">
        <label>
          First name
          <input v-model="firstName" type="text" required />
        </label>
        <label>
          Last name
          <input v-model="lastName" type="text" required />
        </label>
        <label>
          Email
          <input v-model="email" type="email" required />
        </label>
        <label>
          Password
          <input v-model="password" type="password" minlength="8" required />
        </label>
        <label>
          Confirm password
          <input v-model="passwordConfirmation" type="password" minlength="8" required />
        </label>
        <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
        <button type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? "Creating account…" : "Create account" }}
        </button>
      </form>
    </section>
  </AuthLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";
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
