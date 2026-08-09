<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Organiser requests</h1>

    <p v-if="adminStore.isLoadingOrganiserRequests" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.organiserRequestsError" class="text-sm text-red-600">{{ adminStore.organiserRequestsError }}</p>
    <p v-else-if="adminStore.organiserRequests.length === 0" class="text-sm text-gray-500">
      No pending organiser requests.
    </p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="user in adminStore.organiserRequests" :key="user.id" class="p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
          <p class="text-xs text-gray-500 break-words">{{ user.email }} · requested {{ formattedDate(user.organiser_requested_at) }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <PrimaryButton :disabled="isBusy(user.id)" @click="approve(user.id)">Approve</PrimaryButton>
          <SecondaryButton :disabled="isBusy(user.id)" @click="reject(user.id)">Reject</SecondaryButton>
        </div>
      </div>
    </div>
    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import PrimaryButton from "../../components/PrimaryButton.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const adminStore = useAdminStore();
const busyUserIds = reactive(new Set());
const actionError = ref("");

function isBusy(userId) {
  return busyUserIds.has(userId);
}

function formattedDate(timestamp) {
  return timestamp ? new Date(timestamp).toLocaleDateString() : "";
}

async function approve(userId) {
  busyUserIds.add(userId);
  actionError.value = "";
  try {
    await adminStore.approveOrganiser(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't approve that request. Please try again.").message;
  } finally {
    busyUserIds.delete(userId);
  }
}

async function reject(userId) {
  busyUserIds.add(userId);
  actionError.value = "";
  try {
    await adminStore.rejectOrganiser(userId);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't reject that request. Please try again.").message;
  } finally {
    busyUserIds.delete(userId);
  }
}

onMounted(() => {
  adminStore.fetchOrganiserRequests();
});
</script>
