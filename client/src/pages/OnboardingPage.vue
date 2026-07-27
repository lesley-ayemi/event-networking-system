<template>
  <DefaultLayout>
    <h1>Complete your profile</h1>
    <ol class="progress">
      <li :class="{ active: currentStep === 1 }">Profile</li>
      <li :class="{ active: currentStep === 2 }">Preferences</li>
      <li :class="{ active: currentStep === 3 }">Compatibility Quiz</li>
      <li :class="{ active: currentStep === 4 }">Complete</li>
    </ol>

    <section v-if="currentStep === 1">
      <p>Personal information fields will go here (Phase: Profile onboarding).</p>
      <button type="button" @click="currentStep = 2">Continue</button>
    </section>

    <section v-if="currentStep === 2">
      <p>Communication preference fields will go here.</p>
      <button type="button" @click="currentStep = 1">Back</button>
      <button type="button" @click="currentStep = 3">Continue</button>
    </section>

    <section v-if="currentStep === 3">
      <p>The compatibility quiz will be embedded here.</p>
      <button type="button" @click="currentStep = 2">Back</button>
      <button type="button" @click="currentStep = 4">Continue</button>
    </section>

    <section v-if="currentStep === 4">
      <p>You're all set.</p>
      <RouterLink to="/dashboard">Go to your dashboard</RouterLink>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";

const currentStep = ref(1);
</script>
