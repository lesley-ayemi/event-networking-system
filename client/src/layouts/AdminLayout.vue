<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-gray-900">
      <div class="max-w-5xl mx-auto px-6 flex items-center gap-6 h-14 overflow-x-auto">
        <span class="text-sm font-semibold text-white whitespace-nowrap">Admin</span>
        <RouterLink to="/admin/reports" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Reports</RouterLink>
        <RouterLink to="/admin/flagged-accounts" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Flagged</RouterLink>
        <RouterLink to="/admin/organiser-requests" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Organisers</RouterLink>
        <RouterLink to="/admin/events" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Events</RouterLink>
        <RouterLink to="/admin/audit-log" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Audit log</RouterLink>
        <RouterLink to="/admin/admins" class="text-sm font-medium text-gray-300 hover:text-white whitespace-nowrap">Admins</RouterLink>
        <RouterLink to="/dashboard" class="text-sm font-medium text-gray-400 hover:text-white whitespace-nowrap ms-auto shrink-0">
          &larr; Back to app
        </RouterLink>
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
