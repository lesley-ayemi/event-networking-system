<template>
  <DefaultLayout>
    <RouterLink to="/messages" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to messages</RouterLink>

    <div v-if="conversation" class="bg-white shadow-md rounded-lg mt-4 max-w-2xl flex flex-col h-[70vh]">
      <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <Avatar :user="conversation.other_user" />
          <p class="font-semibold text-gray-900 text-sm">
            {{ conversation.other_user.first_name }} {{ conversation.other_user.last_name }}
          </p>
        </div>
        <div class="flex items-center gap-4">
          <button type="button" class="text-xs text-gray-500 hover:text-gray-900" @click="handleReport">Report</button>
          <button type="button" class="text-xs text-gray-500 hover:text-red-600" @click="handleBlock">Block</button>
        </div>
      </div>

      <p v-if="actionStatus" class="text-xs text-gray-500 px-5 pt-3">{{ actionStatus }}</p>

      <div ref="scrollRegion" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <p v-if="conversationsStore.messages.length === 0" class="text-sm text-gray-400 text-center">Say hello 👋</p>
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
import TextInput from "../components/TextInput.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useAuthStore } from "../stores/authStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";

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
