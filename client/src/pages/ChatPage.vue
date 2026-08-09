<template>
  <DefaultLayout>
    <RouterLink to="/messages" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to messages</RouterLink>

    <div v-if="conversation" class="bg-white shadow-md ring-1 ring-gray-100 rounded-xl mt-4 max-w-2xl flex flex-col h-[70vh]">
      <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <RouterLink :to="`/users/${conversation.other_user.id}`" class="flex items-center gap-3 hover:opacity-80">
          <Avatar :user="conversation.other_user" />
          <div>
            <p class="font-semibold text-gray-900 text-sm">
              {{ conversation.other_user.first_name }} {{ conversation.other_user.last_name }}
            </p>
            <AvailabilityBadge
              :status="conversation.other_user.availability_status"
              :updated-at="conversation.other_user.availability_status_updated_at"
              class="mt-0.5"
            />
          </div>
        </RouterLink>
        <div class="flex items-center gap-4 shrink-0">
          <button type="button" class="text-xs text-gray-500 hover:text-gray-900" @click="showUserReportModal = true">
            Report
          </button>
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

      <p v-if="actionError" class="text-xs text-red-600 px-5 pt-3">{{ actionError }}</p>

      <div ref="scrollRegion" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div v-if="conversationsStore.messages.length === 0" class="text-center">
          <p class="text-sm text-gray-500 mb-3">Say hello 👋 — or try an opener:</p>
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
            <p class="text-xs text-gray-500 mt-0.5" :class="isMine(message) ? 'text-right' : 'text-left'">
              {{ formattedTime(message.created_at) }}
              <span v-if="isMine(message) && isRead(message)"> · Read</span>
              <button
                v-if="!isMine(message)"
                type="button"
                class="text-gray-300 hover:text-gray-500 underline"
                @click="reportingMessage = message"
              >
                Report
              </button>
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="handleSend" class="flex items-center gap-3 px-5 py-4 border-t border-gray-100">
        <TextInput v-model="draft" type="text" placeholder="Type a message…" class="flex-1" :disabled="isSending" />
        <PrimaryButton :disabled="isSending || !draft.trim()">Send</PrimaryButton>
      </form>
    </div>

    <ReportModal
      v-if="showUserReportModal && conversation"
      :title="`Report ${conversation.other_user.first_name} ${conversation.other_user.last_name}`"
      reportable-type="user"
      :reportable-id="conversation.other_user.id"
      @close="showUserReportModal = false"
    />
    <ReportModal
      v-if="reportingMessage"
      title="Report this message"
      reportable-type="message"
      :reportable-id="reportingMessage.id"
      @close="reportingMessage = null"
    />
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
import ReportModal from "../components/ReportModal.vue";
import { useUserStore } from "../stores/userStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { CONVERSATION_BOUNDARIES, CONVERSATION_STARTERS } from "../constants/conversationTools.js";
import { getApiError } from "../services/apiError.js";

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const conversationsStore = useConversationsStore();
const friendsStore = useFriendsStore();

const draft = ref("");
const isSending = ref(false);
const scrollRegion = ref(null);
const actionError = ref("");
const showUserReportModal = ref(false);
const reportingMessage = ref(null);

const conversation = computed(() => conversationsStore.currentConversation);

const otherBoundaries = computed(() => {
  const boundaries = conversation.value?.other_user?.conversation_boundaries ?? {};
  return CONVERSATION_BOUNDARIES.filter((boundary) => boundaries[boundary.key]).map((boundary) => boundary.label);
});

function useStarter(starter) {
  draft.value = starter;
}

function isMine(message) {
  return message.sender.id === userStore.user?.id;
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
  actionError.value = "";
  try {
    await conversationsStore.sendMessage(route.params.conversationId, body);
    draft.value = "";
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't send that message. Please try again.").message;
  } finally {
    isSending.value = false;
  }
}

async function handleBlock() {
  const otherId = conversation.value?.other_user?.id;
  if (!otherId) {
    return;
  }
  actionError.value = "";
  try {
    await friendsStore.blockUser(otherId);
    router.push("/messages");
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't block this user. Please try again.").message;
  }
}

onBeforeUnmount(() => {
  conversationsStore.unsubscribeFromConversation();
});
</script>
