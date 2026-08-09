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
          <div v-for="match in group.matches" :key="match.user.id" class="h-full bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 flex flex-col">
            <div class="flex items-start justify-between gap-3">
              <RouterLink :to="`/users/${match.user.id}`" class="flex items-center gap-3 hover:opacity-80">
                <Avatar :user="match.user" />
                <div>
                  <p class="font-semibold text-gray-900 text-sm">{{ match.user.first_name }} {{ match.user.last_name }}</p>
                  <p class="text-xs text-gray-500">{{ personLine(match.user) }}</p>
                  <AvailabilityBadge :status="match.user.availability_status" class="mt-1" />
                </div>
              </RouterLink>
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

            <div class="flex flex-wrap items-center gap-3 mt-auto pt-4">
              <SecondaryButton :disabled="isMessaging(match.user.id)" @click="message(match.user.id)">
                Message
              </SecondaryButton>
              <template v-if="!statusFor(match.user.id)">
                <SecondaryButton :disabled="isBusy(match.user.id)" @click="sendRequest(match.user.id)">
                  Send friend request
                </SecondaryButton>
                <SecondaryButton :disabled="isBusy(match.user.id)" @click="block(match.user.id)">Block</SecondaryButton>
              </template>
              <span v-else class="text-xs text-gray-500">{{ statusFor(match.user.id) }}</span>
            </div>
            <p v-if="messageErrorFor(match.user.id)" class="text-xs text-red-600 mt-2">{{ messageErrorFor(match.user.id) }}</p>
          </div>
        </div>
      </section>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted, reactive } from "vue";
import { RouterLink, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Avatar from "../components/Avatar.vue";
import AvailabilityBadge from "../components/AvailabilityBadge.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useMatchesStore } from "../stores/matchesStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { getApiError } from "../services/apiError.js";

const router = useRouter();
const matchesStore = useMatchesStore();
const friendsStore = useFriendsStore();
const conversationsStore = useConversationsStore();

// Per-match UI state, keyed by the other user's id: "" while actionable,
// a busy flag while a request is in flight, or a short status once resolved.
const busyUserIds = reactive(new Set());
const statusMessages = reactive({});
const messagingUserIds = reactive(new Set());
const messageErrors = reactive({});

function isBusy(userId) {
  return busyUserIds.has(userId);
}

function statusFor(userId) {
  return statusMessages[userId] ?? "";
}

function isMessaging(userId) {
  return messagingUserIds.has(userId);
}

function messageErrorFor(userId) {
  return messageErrors[userId] ?? "";
}

async function message(userId) {
  messagingUserIds.add(userId);
  messageErrors[userId] = "";
  try {
    const conversation = await conversationsStore.startConversation(userId);
    router.push(`/messages/${conversation.id}`);
  } catch (error) {
    messageErrors[userId] = getApiError(error, "We couldn't start that conversation. Please try again.").message;
  } finally {
    messagingUserIds.delete(userId);
  }
}

async function sendRequest(userId) {
  busyUserIds.add(userId);
  try {
    await friendsStore.sendFriendRequest(userId);
    statusMessages[userId] = "Friend request sent";
  } catch (error) {
    statusMessages[userId] = getApiError(error, "We couldn't send that request. Please try again.").message;
  } finally {
    busyUserIds.delete(userId);
  }
}

async function block(userId) {
  busyUserIds.add(userId);
  try {
    await friendsStore.blockUser(userId);
    statusMessages[userId] = "User blocked";
  } catch (error) {
    statusMessages[userId] = getApiError(error, "We couldn't block this user. Please try again.").message;
  } finally {
    busyUserIds.delete(userId);
  }
}

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

function personLine(user) {
  return [user.job_title, user.industry].filter(Boolean).join(" · ");
}

onMounted(() => {
  matchesStore.fetchMatches();
});
</script>
