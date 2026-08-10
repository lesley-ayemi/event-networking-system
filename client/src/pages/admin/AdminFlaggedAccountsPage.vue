<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-1">Flagged accounts</h1>
    <p class="text-sm text-gray-500 mb-6">Accounts with several reports filed against them.</p>

    <p v-if="adminStore.isLoadingFlagged" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.flaggedError" class="text-sm text-red-600">{{ adminStore.flaggedError }}</p>
    <p v-else-if="adminStore.flaggedAccounts.length === 0" class="text-sm text-gray-500">
      No accounts currently meet the flagging threshold.
    </p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="user in adminStore.flaggedAccounts" :key="user.id" class="p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
          <p class="text-xs text-gray-500 break-words">{{ user.email }} · {{ user.reports_count }} reports</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <RouterLink :to="`/admin/users/${user.id}`" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            View
          </RouterLink>
          <SecondaryButton :disabled="isBusy(user.id)" @click="suspend(user.id)">Suspend</SecondaryButton>
        </div>
      </div>
    </div>

    <div
      v-if="adminStore.flaggedPagination && adminStore.flaggedPagination.last_page > 1"
      class="flex flex-wrap items-center justify-between gap-3 mt-6"
    >
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ adminStore.flaggedPagination.current_page }} of {{ adminStore.flaggedPagination.last_page }}
      </span>
      <SecondaryButton :disabled="currentPage === adminStore.flaggedPagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </SecondaryButton>
    </div>

    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const adminStore = useAdminStore();
const busyUserIds = reactive(new Set());
const actionError = ref("");
const currentPage = ref(1);

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

function load() {
  adminStore.fetchFlaggedAccounts({ page: currentPage.value });
}

function goToPage(page) {
  currentPage.value = page;
  load();
}

onMounted(load);
</script>
