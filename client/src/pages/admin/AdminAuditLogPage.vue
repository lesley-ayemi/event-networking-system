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
  </AdminLayout>
</template>

<script setup>
import { onMounted } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import { useAdminStore } from "../../stores/adminStore.js";

const adminStore = useAdminStore();

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

onMounted(() => {
  adminStore.fetchAuditLogs();
});
</script>
