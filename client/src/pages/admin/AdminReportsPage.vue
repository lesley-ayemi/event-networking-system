<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Reports</h1>

    <div class="flex flex-wrap items-center gap-3 mb-4">
      <Select v-model="typeFilter" @change="load">
        <option value="">All types</option>
        <option value="user">Accounts</option>
        <option value="message">Messages</option>
        <option value="event">Events</option>
      </Select>
      <Select v-model="statusFilter" @change="load">
        <option value="">All statuses</option>
        <option v-for="status in STATUSES" :key="status" :value="status">{{ status }}</option>
      </Select>
    </div>

    <p v-if="adminStore.isLoadingReports" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.reportsError" class="text-sm text-red-600">{{ adminStore.reportsError }}</p>
    <p v-else-if="adminStore.reports.length === 0" class="text-sm text-gray-500">No reports match those filters.</p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <div v-for="report in adminStore.reports" :key="report.id" class="p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900">
              {{ reasonLabel(report.reason) }}
              <span class="text-xs text-gray-400 font-normal">· {{ report.reportable_type }} #{{ report.reportable_id }}</span>
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
              Reported by {{ report.reporter?.first_name }} {{ report.reporter?.last_name }}
              on {{ formattedDate(report.created_at) }}
            </p>
            <p v-if="report.details" class="text-sm text-gray-600 mt-2">{{ report.details }}</p>
          </div>
          <Select
            :model-value="report.status"
            class="shrink-0"
            @update:model-value="(value) => updateStatus(report.id, value)"
          >
            <option v-for="status in STATUSES" :key="status" :value="status">{{ status }}</option>
          </Select>
        </div>
      </div>
    </div>
    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import Select from "../../components/Select.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";
import { REPORT_REASONS } from "../../constants/reportReasons.js";

const STATUSES = ["pending", "reviewed", "dismissed", "actioned"];

const adminStore = useAdminStore();
const typeFilter = ref("");
const statusFilter = ref("");
const actionError = ref("");

function reasonLabel(value) {
  return REPORT_REASONS.find((reason) => reason.value === value)?.label ?? value;
}

function formattedDate(timestamp) {
  return new Date(timestamp).toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

function load() {
  adminStore.fetchReports({
    type: typeFilter.value || undefined,
    status: statusFilter.value || undefined,
  });
}

async function updateStatus(reportId, status) {
  actionError.value = "";
  try {
    await adminStore.updateReportStatus(reportId, status);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't update that report. Please try again.").message;
  }
}

onMounted(load);
</script>
