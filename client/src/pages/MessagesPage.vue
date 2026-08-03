<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Messages</h1>

    <p v-if="conversationsStore.isLoading" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="conversationsStore.error" class="text-sm text-red-600">{{ conversationsStore.error }}</p>
    <p v-else-if="conversationsStore.conversations.length === 0" class="text-sm text-gray-500">
      No conversations yet. Start one from a match or friend's profile.
    </p>

    <div v-else class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
      <ConversationPreview
        v-for="conversation in conversationsStore.conversations"
        :key="conversation.id"
        :conversation="conversation"
      />
    </div>
  </DefaultLayout>
</template>

<script setup>
import { onMounted } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import ConversationPreview from "../components/ConversationPreview.vue";
import { useConversationsStore } from "../stores/conversationsStore.js";

const conversationsStore = useConversationsStore();

onMounted(() => {
  conversationsStore.fetchConversations();
});
</script>
