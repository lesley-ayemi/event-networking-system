<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-1">Flagged accounts</h1>
    <p class="text-sm text-gray-500 mb-6">Accounts with several reports filed against them.</p>

    <p v-if="adminStore.isLoadingFlagged" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.flaggedError" class="text-sm text-red-600">{{ adminStore.flaggedError }}</p>
    <p v-else-if="adminStore.flaggedAccounts.length === 0" class="text-sm text-gray-500">
      No accounts currently meet the flagging threshold.
    </p>

    <div v-else class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
      <div v-for="user in adminStore.flaggedAccounts" :key="user.id" class="p-4 flex items-center justify-between gap-3">
        <div>
          <p class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
          <p class="text-xs text-gray-500">{{ user.email }} · {{ user.reports_count }} reports</p>
        </div>
        <SecondaryButton :disabled="isBusy(user.id)" @click="suspend(user.id)">Suspend</SecondaryButton>
      </div>
    </div>
    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const adminStore = useAdminStore();
const busyUserIds = reactive(new Set());
const actionError = ref("");

function isBusy(userId) {
  return busyUserIds.has(userId);
}

async function suspend(userId) {
  busyUserIds.add(userId);
  actionError.value = "";
  try {
    await adminStore.suspendUser(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't suspend that account. Please try again.").message;
  } finally {
    busyUserIds.delete(userId);
  }
}

onMounted(() => {
  adminStore.fetchFlaggedAccounts();
});
</script>
