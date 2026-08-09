<template>
  <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3 min-w-0 flex-1">
      <Avatar :user="request.sender" />
      <p class="font-semibold text-gray-900 text-sm truncate">
        {{ request.sender.first_name }} {{ request.sender.last_name }}
      </p>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <PrimaryButton :disabled="isBusy" @click="accept">Accept</PrimaryButton>
      <SecondaryButton :disabled="isBusy" @click="decline">Decline</SecondaryButton>
    </div>
    <p v-if="errorMessage" class="text-xs text-red-600 mt-2 basis-full">{{ errorMessage }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";
import Avatar from "./Avatar.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";
import { useFriendsStore } from "../stores/friendsStore.js";
import { getApiError } from "../services/apiError.js";

const props = defineProps({
  request: { type: Object, required: true },
});

const friendsStore = useFriendsStore();
const isBusy = ref(false);
const errorMessage = ref("");

async function accept() {
  isBusy.value = true;
  errorMessage.value = "";
  try {
    await friendsStore.acceptRequest(props.request.id);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't accept that request. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function decline() {
  isBusy.value = true;
  errorMessage.value = "";
  try {
    await friendsStore.declineRequest(props.request.id);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't decline that request. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}
</script>
