<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Audit log</h1>

    <p v-if="adminStore.isLoadingAuditLogs" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.auditLogsError" class="text-sm text-red-600">{{ adminStore.auditLogsError }}</p>
    <p v-else-if="adminStore.auditLogs.length === 0" class="text-sm text-gray-500">No admin actions recorded yet.</p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="log in adminStore.auditLogs" :key="log.id" class="p-4">
        <p class="text-sm text-gray-900">
          <span class="font-medium">{{ log.admin?.first_name }} {{ log.admin?.last_name }}</span>
          — {{ log.action }}
          <span v-if="log.subject_type" class="text-gray-500">
            ({{ log.subject_type }} #{{ log.subject_id }})
          </span>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">{{ formattedDate(log.created_at) }}</p>
      </div>
    </div>

    <div
      v-if="adminStore.auditLogsPagination && adminStore.auditLogsPagination.last_page > 1"
      class="flex flex-wrap items-center justify-between gap-3 mt-6"
    >
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ adminStore.auditLogsPagination.current_page }} of {{ adminStore.auditLogsPagination.last_page }}
      </span>
      <SecondaryButton
        :disabled="currentPage === adminStore.auditLogsPagination.last_page"
        @click="goToPage(currentPage + 1)"
      >
        Next
      </SecondaryButton>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";

const adminStore = useAdminStore();
const currentPage = ref(1);

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

function load() {
  adminStore.fetchAuditLogs({ page: currentPage.value });
}

function goToPage(page) {
  currentPage.value = page;
  load();
}

onMounted(load);
</script>
