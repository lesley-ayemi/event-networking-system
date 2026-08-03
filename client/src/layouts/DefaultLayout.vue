<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-5xl mx-auto px-6 flex items-center gap-6 h-14 overflow-x-auto">
        <RouterLink to="/dashboard" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Dashboard</RouterLink>
        <RouterLink to="/events" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Events</RouterLink>
        <RouterLink to="/saved-events" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Saved</RouterLink>
        <RouterLink to="/matches" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Matches</RouterLink>
        <RouterLink to="/friends" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Friends</RouterLink>
        <RouterLink to="/messages" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Messages</RouterLink>
        <RouterLink to="/profile" class="text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">Profile</RouterLink>
      </div>
    </nav>
    <main class="max-w-5xl mx-auto px-6 py-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>
