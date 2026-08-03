<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Matches</h1>

    <p v-if="matchesStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="matchesStore.error" class="text-sm text-red-600">{{ matchesStore.error }}</p>
    <p v-else-if="groupedMatches.length === 0" class="text-sm text-gray-500">
      No matches yet. Register for an event and opt in to matching to see compatible people here.
    </p>

    <div v-else class="space-y-8">
      <section v-for="group in groupedMatches" :key="group.event.id">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
          {{ group.event.name }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="match in group.matches" :key="match.user.id" class="bg-white shadow-sm rounded-lg p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="match.user.profile_image"
                  :src="match.user.profile_image"
                  alt=""
                  class="w-10 h-10 rounded-full object-cover bg-gray-100"
                />
                <div v-else class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold shrink-0">
                  {{ initials(match.user) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-900 text-sm">{{ match.user.first_name }} {{ match.user.last_name }}</p>
                  <p class="text-xs text-gray-500">{{ personLine(match.user) }}</p>
                </div>
              </div>
              <span class="shrink-0 inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                {{ match.score }}% compatible
              </span>
            </div>

            <div v-if="match.reasons.length > 0" class="mt-4">
              <p class="text-xs font-medium text-gray-900 mb-1">You both:</p>
              <ul class="text-xs text-gray-600 space-y-0.5 list-disc list-inside">
                <li v-for="reason in match.reasons" :key="reason">{{ reason }}</li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import { useMatchesStore } from "../stores/matchesStore.js";

const matchesStore = useMatchesStore();

const groupedMatches = computed(() => {
  const groups = new Map();
  for (const match of matchesStore.matches) {
    if (!groups.has(match.event.id)) {
      groups.set(match.event.id, { event: match.event, matches: [] });
    }
    groups.get(match.event.id).matches.push(match);
  }
  return Array.from(groups.values());
});

function initials(user) {
  return `${user.first_name?.charAt(0) ?? ""}${user.last_name?.charAt(0) ?? ""}`.toUpperCase();
}

function personLine(user) {
  return [user.job_title, user.industry].filter(Boolean).join(" · ");
}

onMounted(() => {
  matchesStore.fetchMatches();
});
</script>
