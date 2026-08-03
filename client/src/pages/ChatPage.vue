<template>
  <DefaultLayout>
    <RouterLink to="/messages" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to messages</RouterLink>

    <div v-if="conversation" class="bg-white shadow-md rounded-lg mt-4 max-w-2xl flex flex-col h-[70vh]">
      <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <Avatar :user="conversation.other_user" />
          <div>
            <p class="font-semibold text-gray-900 text-sm">
              {{ conversation.other_user.first_name }} {{ conversation.other_user.last_name }}
            </p>
            <AvailabilityBadge :status="conversation.other_user.availability_status" class="mt-0.5" />
          </div>
        </div>
        <div class="flex items-center gap-4">
          <button type="button" class="text-xs text-gray-500 hover:text-gray-900" @click="handleReport">Report</button>
          <button type="button" class="text-xs text-gray-500 hover:text-red-600" @click="handleBlock">Block</button>
        </div>
      </div>

      <div v-if="otherBoundaries.length > 0" class="flex flex-wrap gap-1.5 px-5 pt-3">
        <span
          v-for="boundary in otherBoundaries"
          :key="boundary"
          class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"
        >
          {{ boundary }}
        </span>
      </div>

      <p v-if="actionStatus" class="text-xs text-gray-500 px-5 pt-3">{{ actionStatus }}</p>

      <div ref="scrollRegion" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div v-if="conversationsStore.messages.length === 0" class="text-center">
          <p class="text-sm text-gray-400 mb-3">Say hello 👋 — or try an opener:</p>
          <div class="flex flex-wrap justify-center gap-2">
            <button
              v-for="starter in CONVERSATION_STARTERS"
              :key="starter"
              type="button"
              class="text-xs px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50"
              @click="useStarter(starter)"
            >
              {{ starter }}
            </button>
          </div>
        </div>
        <div
          v-for="message in conversationsStore.messages"
          :key="message.id"
          class="flex"
          :class="isMine(message) ? 'justify-end' : 'justify-start'"
        >
          <div class="max-w-xs">
            <div
              class="px-3 py-2 rounded-lg text-sm"
              :class="isMine(message) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'"
            >
              {{ message.body }}
            </div>
            <p class="text-xs text-gray-400 mt-0.5" :class="isMine(message) ? 'text-right' : 'text-left'">
              {{ formattedTime(message.created_at) }}
              <span v-if="isMine(message) && isRead(message)"> · Read</span>
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="handleSend" class="flex items-center gap-3 px-5 py-4 border-t border-gray-100">
        <TextInput v-model="draft" type="text" placeholder="Type a message…" class="flex-1" />
        <PrimaryButton :disabled="isSending || !draft.trim()">Send</PrimaryButton>
      </form>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Avatar from "../components/Avatar.vue";
import AvailabilityBadge from "../components/AvailabilityBadge.vue";
import TextInput from "../components/TextInput.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { CONVERSATION_BOUNDARIES, CONVERSATION_STARTERS } from "../constants/conversationTools.js";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const conversationsStore = useConversationsStore();
const friendsStore = useFriendsStore();

const draft = ref("");
const isSending = ref(false);
const scrollRegion = ref(null);
const actionStatus = ref("");

const conversation = computed(() => conversationsStore.currentConversation);

const otherBoundaries = computed(() => {
  const boundaries = conversation.value?.other_user?.conversation_boundaries ?? {};
  return CONVERSATION_BOUNDARIES.filter((boundary) => boundaries[boundary.key]).map((boundary) => boundary.label);
});

function useStarter(starter) {
  draft.value = starter;
}

function isMine(message) {
  return message.sender.id === authStore.user?.id;
}

function isRead(message) {
  const otherReadAt = conversation.value?.other_last_read_at;
  return Boolean(otherReadAt) && new Date(message.created_at) <= new Date(otherReadAt);
}

function formattedTime(timestamp) {
  return new Date(timestamp).toLocaleTimeString(undefined, { timeStyle: "short" });
}

async function scrollToBottom() {
  await nextTick();
  if (scrollRegion.value) {
    scrollRegion.value.scrollTop = scrollRegion.value.scrollHeight;
  }
}

async function loadConversation(conversationId) {
  conversationsStore.unsubscribeFromConversation();
  conversationsStore.messages = [];
  actionStatus.value = "";

  await Promise.all([
    conversationsStore.fetchConversation(conversationId),
    conversationsStore.fetchMessages(conversationId),
  ]);
  conversationsStore.subscribeToConversation(conversationId);
  await conversationsStore.markRead(conversationId);
  scrollToBottom();
}

watch(() => route.params.conversationId, (conversationId) => {
  if (conversationId) {
    loadConversation(conversationId);
  }
}, { immediate: true });

watch(() => conversationsStore.messages.length, () => {
  scrollToBottom();
  if (route.params.conversationId) {
    conversationsStore.markRead(route.params.conversationId);
  }
});

async function handleSend() {
  const body = draft.value.trim();
  if (!body) {
    return;
  }
  isSending.value = true;
  try {
    await conversationsStore.sendMessage(route.params.conversationId, body);
    draft.value = "";
  } finally {
    isSending.value = false;
  }
}

async function handleReport() {
  const reason = window.prompt("What's going on? This is optional.");
  if (reason === null) {
    return;
  }
  await conversationsStore.reportConversation(route.params.conversationId, reason || null);
  actionStatus.value = "Thanks — we've received your report.";
}

async function handleBlock() {
  const otherId = conversation.value?.other_user?.id;
  if (!otherId) {
    return;
  }
  await friendsStore.blockUser(otherId);
  router.push("/messages");
}

onBeforeUnmount(() => {
  conversationsStore.unsubscribeFromConversation();
});
</script>
