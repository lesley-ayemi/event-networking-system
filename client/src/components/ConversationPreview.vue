<template>
  <RouterLink
    :to="`/messages/${conversation.id}`"
    class="flex items-center gap-3 px-5 py-4 hover:bg-gray-50"
  >
    <Avatar :user="conversation.other_user" />
    <div class="min-w-0 flex-1">
      <div class="flex items-center justify-between gap-2">
        <p class="font-semibold text-gray-900 text-sm truncate">
          {{ conversation.other_user.first_name }} {{ conversation.other_user.last_name }}
        </p>
        <span v-if="conversation.last_message" class="text-xs text-gray-500 shrink-0">
          {{ formattedTime(conversation.last_message.created_at) }}
        </span>
      </div>
      <p class="text-sm text-gray-500 truncate">
        {{ conversation.last_message ? conversation.last_message.body : "No messages yet" }}
      </p>
    </div>
    <span
      v-if="conversation.unread_count > 0"
      class="shrink-0 inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-indigo-600 text-white text-xs font-medium"
    >
      {{ conversation.unread_count }}
    </span>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from "vue-router";
import Avatar from "./Avatar.vue";

defineProps({
  conversation: { type: Object, required: true },
});

function formattedTime(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "short", timeStyle: "short" });
}
</script>
