<template>
  <div class="h-full bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-4 flex flex-col">
    <div class="flex items-center justify-between gap-2">
      <RouterLink :to="`/users/${match.user.id}`" class="flex items-center gap-3 min-w-0 hover:opacity-80">
        <Avatar :user="match.user" />
        <div class="min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">{{ match.user.first_name }} {{ match.user.last_name }}</p>
          <AvailabilityBadge :status="match.user.availability_status" class="mt-0.5" />
        </div>
      </RouterLink>
      <span class="shrink-0 inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
        {{ match.score }}%
      </span>
    </div>

    <p v-if="match.reasons[0]" class="text-xs text-gray-500 mt-2">{{ match.reasons[0] }}</p>

    <div class="mt-auto pt-3">
      <SecondaryButton :disabled="isMessaging" @click="message">Message</SecondaryButton>
      <p v-if="errorMessage" class="text-xs text-red-600 mt-2">{{ errorMessage }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import Avatar from "./Avatar.vue";
import AvailabilityBadge from "./AvailabilityBadge.vue";
import SecondaryButton from "./SecondaryButton.vue";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { getApiError } from "../services/apiError.js";

const props = defineProps({
  match: { type: Object, required: true },
});

const router = useRouter();
const conversationsStore = useConversationsStore();

const isMessaging = ref(false);
const errorMessage = ref("");

async function message() {
  isMessaging.value = true;
  errorMessage.value = "";
  try {
    const conversation = await conversationsStore.startConversation(props.match.user.id);
    router.push(`/messages/${conversation.id}`);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't start that conversation. Please try again.").message;
  } finally {
    isMessaging.value = false;
  }
}
</script>
