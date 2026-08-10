<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <RouterLink
        v-for="stat in stats"
        :key="stat.label"
        :to="stat.to"
        class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 hover:ring-gray-200 transition"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="stat.badgeClass">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-500">{{ stat.label }}</p>
            <p class="text-xl font-semibold text-gray-900">
              {{ isLoading ? "…" : stat.value }}
            </p>
          </div>
        </div>
      </RouterLink>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl mt-6">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-900">Recent activity</h2>
        <RouterLink to="/admin/audit-log" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
          View all
        </RouterLink>
      </div>

      <p v-if="adminStore.isLoadingAuditLogs" class="text-sm text-gray-500 px-5 py-4">Loading…</p>
      <p v-else-if="adminStore.auditLogs.length === 0" class="text-sm text-gray-500 px-5 py-4">
        No admin actions recorded yet.
      </p>
      <div v-else class="divide-y divide-gray-100">
        <div v-for="log in adminStore.auditLogs.slice(0, 5)" :key="log.id" class="px-5 py-3">
          <p class="text-sm text-gray-900">
            <span class="font-medium">{{ log.admin?.first_name }} {{ log.admin?.last_name }}</span>
            — {{ log.action }}
            <span v-if="log.subject_type" class="text-gray-500">({{ log.subject_type }} #{{ log.subject_id }})</span>
          </p>
          <p class="text-xs text-gray-500 mt-0.5">{{ formattedDate(log.created_at) }}</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import { useAdminStore } from "../../stores/adminStore.js";

const adminStore = useAdminStore();
const isLoading = ref(true);

const totalUsers = ref(0);
const pendingReports = ref(0);
const flaggedCount = ref(0);
const pendingOrganiserRequests = ref(0);

const stats = computed(() => [
  {
    label: "Total users",
    value: totalUsers.value,
    to: "/admin/users",
    badgeClass: "bg-indigo-50 text-indigo-600",
    icon: "M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z",
  },
  {
    label: "Pending reports",
    value: pendingReports.value,
    to: "/admin/reports",
    badgeClass: "bg-red-50 text-red-600",
    icon: "M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5",
  },
  {
    label: "Flagged accounts",
    value: flaggedCount.value,
    to: "/admin/flagged-accounts",
    badgeClass: "bg-amber-50 text-amber-600",
    icon: "M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z",
  },
  {
    label: "Organiser requests",
    value: pendingOrganiserRequests.value,
    to: "/admin/organiser-requests",
    badgeClass: "bg-blue-50 text-blue-600",
    icon: "M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6.75-10.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm3.75 7.5c0-1.864-2.015-3-4.5-3s-4.5 1.136-4.5 3v.75h9v-.75Z",
  },
]);

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

onMounted(async () => {
  isLoading.value = true;
  await Promise.all([
    adminStore.fetchUsers({}).then(() => {
      totalUsers.value = adminStore.usersPagination?.total ?? 0;
    }),
    adminStore.fetchReports({ status: "pending" }).then(() => {
      pendingReports.value = adminStore.reportsPagination?.total ?? 0;
    }),
    adminStore.fetchFlaggedAccounts().then(() => {
      flaggedCount.value = adminStore.flaggedAccounts.length;
    }),
    adminStore.fetchOrganiserRequests().then(() => {
      pendingOrganiserRequests.value = adminStore.organiserRequests.length;
    }),
    adminStore.fetchAuditLogs({ page: 1 }),
  ]);
  isLoading.value = false;
});
</script>
