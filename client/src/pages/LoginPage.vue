<template>
  <AuthLayout>
    <section>
      <h1>Log in</h1>
      <form @submit.prevent="handleSubmit">
        <label>
          Email
          <input v-model="email" type="email" required />
        </label>
        <label>
          Password
          <input v-model="password" type="password" required />
        </label>
        <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
        <button type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? "Logging in…" : "Log in" }}
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
    errorMessage.value = "We couldn't log you in. Please check your email and password.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>
