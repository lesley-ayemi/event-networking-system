<template>
  <AuthLayout>
    <section>
      <h1>Find your people, at your own pace.</h1>
      <p>
        Discover events, choose how you want to interact, and message before
        you ever have to make small talk in person.
      </p>
      <RouterLink to="/register">Get started</RouterLink>
      <RouterLink to="/login">Log in</RouterLink>
    </section>
  </AuthLayout>
</template>

<script setup>
import { RouterLink } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";
</script>
