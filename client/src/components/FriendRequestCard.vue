<template>
  <div class="bg-white shadow-sm rounded-lg p-4 flex items-center justify-between gap-3">
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
  </div>
</template>

<script setup>
import { ref } from "vue";
import Avatar from "./Avatar.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";
import { useFriendsStore } from "../stores/friendsStore.js";

const props = defineProps({
  request: { type: Object, required: true },
});

const friendsStore = useFriendsStore();
const isBusy = ref(false);

async function accept() {
  isBusy.value = true;
  try {
    await friendsStore.acceptRequest(props.request.id);
  } finally {
    isBusy.value = false;
  }
}

async function decline() {
  isBusy.value = true;
  try {
    await friendsStore.declineRequest(props.request.id);
  } finally {
    isBusy.value = false;
  }
}
</script>
