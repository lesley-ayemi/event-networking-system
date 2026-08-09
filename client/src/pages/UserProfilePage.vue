<template>
  <DefaultLayout>
    <RouterLink to="/matches" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back</RouterLink>

    <p v-if="usersStore.isLoadingProfile" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="usersStore.profileError" class="text-sm text-red-600 mt-4">{{ usersStore.profileError }}</p>

    <div v-else-if="profile" class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6 mt-4 max-w-xl">
      <div class="flex items-center gap-4">
        <img
          v-if="profile.profile_image"
          :src="profile.profile_image"
          alt=""
          class="w-20 h-20 rounded-full object-cover bg-gray-100 ring-2 ring-white shadow-sm"
        />
        <div
          v-else
          class="w-20 h-20 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-semibold shrink-0 ring-2 ring-white shadow-sm"
        >
          {{ initials }}
        </div>
        <div class="min-w-0">
          <h1 class="text-lg font-semibold text-gray-900">{{ profile.first_name }} {{ profile.last_name }}</h1>
          <p v-if="profile.job_title || profile.industry" class="text-sm text-gray-500">
            {{ [profile.job_title, profile.industry].filter(Boolean).join(" · ") }}
          </p>
          <AvailabilityBadge :status="profile.availability_status" class="mt-1.5" />
        </div>
      </div>

      <div v-if="profile.bio" class="mt-6">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">About</h2>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ profile.bio }}</p>
      </div>

      <div v-if="profile.networking_goals" class="mt-6">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Looking to</h2>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ profile.networking_goals }}</p>
      </div>

      <div class="mt-6 pt-5 border-t border-gray-100">
        <SecondaryButton :disabled="isMessaging" @click="message">
          {{ isMessaging ? "Starting…" : "Message" }}
        </SecondaryButton>
        <p v-if="messageError" class="text-xs text-red-600 mt-2">{{ messageError }}</p>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import AvailabilityBadge from "../components/AvailabilityBadge.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useUsersStore } from "../stores/usersStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { getApiError } from "../services/apiError.js";

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const conversationsStore = useConversationsStore();

const isMessaging = ref(false);
const messageError = ref("");

const profile = computed(() => usersStore.viewedProfile);

const initials = computed(
  () => `${profile.value?.first_name?.charAt(0) ?? ""}${profile.value?.last_name?.charAt(0) ?? ""}`.toUpperCase()
);

async function message() {
  isMessaging.value = true;
  messageError.value = "";
  try {
    const conversation = await conversationsStore.startConversation(profile.value.id);
    router.push(`/messages/${conversation.id}`);
  } catch (error) {
    messageError.value = getApiError(error, "We couldn't start that conversation. Please try again.").message;
  } finally {
    isMessaging.value = false;
  }
}

watch(() => route.params.id, (id) => {
  if (id) {
    usersStore.fetchUserProfile(id);
  }
}, { immediate: true });
</script>
