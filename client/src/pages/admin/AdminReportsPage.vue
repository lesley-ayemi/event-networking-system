<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Reports</h1>

    <div class="flex flex-wrap items-center gap-3 mb-4">
      <Select v-model="typeFilter" @change="applyFilters">
        <option value="">All types</option>
        <option value="user">Accounts</option>
        <option value="message">Messages</option>
        <option value="event">Events</option>
      </Select>
      <Select v-model="statusFilter" @change="applyFilters">
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
              <span class="text-xs text-gray-500 font-normal">· {{ report.reportable_type }} #{{ report.reportable_id }}</span>
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
              Reported by {{ report.reporter?.first_name }} {{ report.reporter?.last_name }}
              on {{ formattedDate(report.created_at) }}
            </p>
            <p v-if="report.details" class="text-sm text-gray-600 mt-2">{{ report.details }}</p>
            <button
              v-if="report.reportable_type === 'message'"
              type="button"
              class="text-xs font-medium text-indigo-600 hover:text-indigo-700 mt-2"
              @click="toggleContext(report.id)"
            >
              {{ expandedReportId === report.id ? "Hide conversation" : "View conversation" }}
            </button>
          </div>
          <Select
            :model-value="report.status"
            class="shrink-0"
            @update:model-value="(value) => updateStatus(report.id, value)"
          >
            <option v-for="status in STATUSES" :key="status" :value="status">{{ status }}</option>
          </Select>
        </div>

        <div v-if="expandedReportId === report.id" class="mt-3 bg-gray-50 rounded-lg p-3 space-y-2">
          <p v-if="adminStore.isLoadingReportContext" class="text-xs text-gray-500">Loading…</p>
          <p v-else-if="adminStore.reportContextError" class="text-xs text-red-600">{{ adminStore.reportContextError }}</p>
          <div
            v-else
            v-for="message in adminStore.reportContext"
            :key="message.id"
            class="text-xs rounded-lg p-2"
            :class="message.is_flagged ? 'bg-red-50 ring-1 ring-red-200' : ''"
          >
            <p class="font-medium text-gray-900">
              {{ message.sender?.first_name }} {{ message.sender?.last_name }}
              <span v-if="message.is_flagged" class="text-red-600 font-semibold">· Reported message</span>
            </p>
            <p class="text-gray-600 mt-0.5">{{ message.body }}</p>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="adminStore.reportsPagination && adminStore.reportsPagination.last_page > 1"
      class="flex flex-wrap items-center justify-between gap-3 mt-6"
    >
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ adminStore.reportsPagination.current_page }} of {{ adminStore.reportsPagination.last_page }}
      </span>
      <SecondaryButton
        :disabled="currentPage === adminStore.reportsPagination.last_page"
        @click="goToPage(currentPage + 1)"
      >
        Next
      </SecondaryButton>
    </div>

    <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import Select from "../../components/Select.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";
import { REPORT_REASONS } from "../../constants/reportReasons.js";

const STATUSES = ["pending", "reviewed", "dismissed", "actioned"];

const adminStore = useAdminStore();
const typeFilter = ref("");
const statusFilter = ref("");
const actionError = ref("");
const currentPage = ref(1);
const expandedReportId = ref(null);

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
    page: currentPage.value,
  });
}

function applyFilters() {
  currentPage.value = 1;
  load();
}

function goToPage(page) {
  currentPage.value = page;
  load();
}

async function toggleContext(reportId) {
  if (expandedReportId.value === reportId) {
    expandedReportId.value = null;
    adminStore.clearReportContext();
    return;
  }
  expandedReportId.value = reportId;
  await adminStore.fetchReportContext(reportId);
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
