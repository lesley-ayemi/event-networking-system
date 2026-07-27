<template>
  <div class="default-layout">
    <nav>
      <RouterLink to="/dashboard">Dashboard</RouterLink>
      <RouterLink to="/events">Events</RouterLink>
      <RouterLink to="/saved-events">Saved</RouterLink>
      <RouterLink to="/matches">Matches</RouterLink>
      <RouterLink to="/friends">Friends</RouterLink>
      <RouterLink to="/messages">Messages</RouterLink>
      <RouterLink to="/profile">Profile</RouterLink>
    </nav>
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>

<style scoped>
.default-layout main {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
