<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Friends</h1>

    <section class="mb-8">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Find people</h2>
      <form class="flex gap-3" @submit.prevent="search">
        <TextInput v-model="searchQuery" type="text" placeholder="Search by name" class="flex-1" />
        <PrimaryButton type="submit" :disabled="friendsStore.isSearching || !searchQuery.trim()">Search</PrimaryButton>
      </form>

      <p v-if="friendsStore.isSearching" class="text-sm text-gray-500 mt-3">Searching…</p>
      <p v-else-if="friendsStore.searchError" class="text-sm text-red-600 mt-3">{{ friendsStore.searchError }}</p>
      <p v-else-if="hasSearched && friendsStore.searchResults.length === 0" class="text-sm text-gray-500 mt-3">
        No one matched that search.
      </p>

      <div v-if="friendsStore.searchResults.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
        <div v-for="person in friendsStore.searchResults" :key="person.id" class="h-full bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 flex flex-col">
          <RouterLink :to="`/users/${person.id}`" class="flex items-center gap-3 hover:opacity-80">
            <Avatar :user="person" />
            <div>
              <p class="font-semibold text-gray-900 text-sm">{{ person.first_name }} {{ person.last_name }}</p>
              <p class="text-xs text-gray-500">{{ personLine(person) }}</p>
            </div>
          </RouterLink>
          <div class="mt-auto pt-4">
            <SecondaryButton
              v-if="relationshipStatus(person.id) === null"
              :disabled="isBusy"
              @click="sendRequest(person.id)"
            >
              Send friend request
            </SecondaryButton>
            <span v-else class="text-xs text-gray-500">{{ relationshipStatus(person.id) }}</span>
          </div>
        </div>
      </div>
    </section>

    <p v-if="friendsStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="friendsStore.error" class="text-sm text-red-600">{{ friendsStore.error }}</p>
    <p v-if="actionError" class="text-sm text-red-600 mb-4">{{ actionError }}</p>

    <div v-else class="space-y-8">
      <section v-if="friendsStore.incomingRequests.length > 0">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Friend requests</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="request in friendsStore.incomingRequests" :key="request.id" class="h-full bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 flex flex-col">
            <RouterLink :to="`/users/${request.sender.id}`" class="flex items-center gap-3 hover:opacity-80">
              <Avatar :user="request.sender" />
              <div>
                <p class="font-semibold text-gray-900 text-sm">{{ request.sender.first_name }} {{ request.sender.last_name }}</p>
                <p class="text-xs text-gray-500">{{ personLine(request.sender) }}</p>
              </div>
            </RouterLink>
            <div class="flex items-center gap-3 mt-auto pt-4">
              <PrimaryButton :disabled="isBusy" @click="accept(request.id)">Accept</PrimaryButton>
              <SecondaryButton :disabled="isBusy" @click="decline(request.id)">Decline</SecondaryButton>
            </div>
          </div>
        </div>
      </section>

      <section v-if="friendsStore.outgoingRequests.length > 0">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Sent requests</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="request in friendsStore.outgoingRequests" :key="request.id" class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5">
            <RouterLink :to="`/users/${request.recipient.id}`" class="flex items-center gap-3 hover:opacity-80">
              <Avatar :user="request.recipient" />
              <div>
                <p class="font-semibold text-gray-900 text-sm">{{ request.recipient.first_name }} {{ request.recipient.last_name }}</p>
                <p class="text-xs text-gray-500">Request pending</p>
              </div>
            </RouterLink>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Your friends</h2>
        <p v-if="friendsStore.friends.length === 0" class="text-sm text-gray-500">
          You don't have any friends yet. Accept a request above, or send one from your matches.
        </p>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="friend in friendsStore.friends" :key="friend.id" class="h-full bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 flex flex-col">
            <RouterLink :to="`/users/${friend.id}`" class="flex items-center gap-3 hover:opacity-80">
              <Avatar :user="friend" />
              <div>
                <p class="font-semibold text-gray-900 text-sm">{{ friend.first_name }} {{ friend.last_name }}</p>
                <p class="text-xs text-gray-500">{{ personLine(friend) }}</p>
                <AvailabilityBadge
                  :status="friend.availability_status"
                  :updated-at="friend.availability_status_updated_at"
                  class="mt-1"
                />
              </div>
            </RouterLink>
            <div class="flex flex-wrap items-center gap-3 mt-auto pt-4">
              <SecondaryButton :disabled="isMessaging(friend.id)" @click="message(friend.id)">Message</SecondaryButton>
              <SecondaryButton :disabled="isBusy" @click="remove(friend.id)">Remove</SecondaryButton>
              <DangerButton :disabled="isBusy" @click="block(friend.id)">Block</DangerButton>
            </div>
            <p v-if="messageErrorFor(friend.id)" class="text-xs text-red-600 mt-2">{{ messageErrorFor(friend.id) }}</p>
          </div>
        </div>
      </section>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import { RouterLink, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Avatar from "../components/Avatar.vue";
import AvailabilityBadge from "../components/AvailabilityBadge.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import DangerButton from "../components/DangerButton.vue";
import TextInput from "../components/TextInput.vue";
import { useFriendsStore } from "../stores/friendsStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { getApiError } from "../services/apiError.js";

const router = useRouter();
const friendsStore = useFriendsStore();
const conversationsStore = useConversationsStore();
const isBusy = ref(false);
const actionError = ref("");
const messagingUserIds = reactive(new Set());
const messageErrors = reactive({});
const searchQuery = ref("");
const hasSearched = ref(false);

function search() {
  hasSearched.value = true;
  friendsStore.searchUsers(searchQuery.value);
}

function relationshipStatus(userId) {
  if (friendsStore.friends.some((friend) => friend.id === userId)) return "Already friends";
  if (friendsStore.outgoingRequests.some((request) => request.recipient.id === userId)) return "Request sent";
  if (friendsStore.incomingRequests.some((request) => request.sender.id === userId)) return "Check your requests";
  return null;
}

async function sendRequest(userId) {
  isBusy.value = true;
  actionError.value = "";
  try {
    await friendsStore.sendFriendRequest(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't send that request. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
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

function personLine(user) {
  return [user.job_title, user.industry].filter(Boolean).join(" · ");
}

async function accept(requestId) {
  isBusy.value = true;
  actionError.value = "";
  try {
    await friendsStore.acceptRequest(requestId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't accept that request. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function decline(requestId) {
  isBusy.value = true;
  actionError.value = "";
  try {
    await friendsStore.declineRequest(requestId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't decline that request. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function remove(userId) {
  isBusy.value = true;
  actionError.value = "";
  try {
    await friendsStore.removeFriend(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't remove that friend. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function block(userId) {
  isBusy.value = true;
  actionError.value = "";
  try {
    await friendsStore.blockUser(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't block this user. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

onMounted(() => {
  friendsStore.fetchAll();
});
</script>
