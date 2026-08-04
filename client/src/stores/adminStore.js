import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useAdminStore = defineStore("admin", {
  state: () => ({
    reports: [],
    isLoadingReports: false,
    reportsError: "",

    flaggedAccounts: [],
    isLoadingFlagged: false,
    flaggedError: "",

    organiserRequests: [],
    isLoadingOrganiserRequests: false,
    organiserRequestsError: "",

    auditLogs: [],
    isLoadingAuditLogs: false,
    auditLogsError: "",

    admins: [],
    isLoadingAdmins: false,
    adminsError: "",
  }),

  actions: {
    async fetchReports(filters = {}) {
      this.isLoadingReports = true;
      this.reportsError = "";
      try {
        const response = await apiClient.get("/admin/reports", { params: filters });
        this.reports = response.data.data;
      } catch (error) {
        this.reportsError = getApiError(error, "We couldn't load reports right now. Please try again.").message;
      } finally {
        this.isLoadingReports = false;
      }
    },

    async updateReportStatus(reportId, status) {
      const response = await apiClient.patch(`/admin/reports/${reportId}`, { status });
      const index = this.reports.findIndex((report) => report.id === reportId);
      if (index !== -1) {
        this.reports[index] = response.data;
      }
      return response.data;
    },

    async fetchFlaggedAccounts() {
      this.isLoadingFlagged = true;
      this.flaggedError = "";
      try {
        const response = await apiClient.get("/admin/flagged-accounts");
        this.flaggedAccounts = response.data.data;
      } catch (error) {
        this.flaggedError = getApiError(error, "We couldn't load flagged accounts right now. Please try again.").message;
      } finally {
        this.isLoadingFlagged = false;
      }
    },

    async suspendUser(userId) {
      await apiClient.post(`/admin/users/${userId}/suspend`);
      this.flaggedAccounts = this.flaggedAccounts.filter((user) => user.id !== userId);
    },

    async unsuspendUser(userId) {
      await apiClient.post(`/admin/users/${userId}/unsuspend`);
    },

    async removeEvent(eventId) {
      await apiClient.delete(`/admin/events/${eventId}`);
    },

    async fetchOrganiserRequests() {
      this.isLoadingOrganiserRequests = true;
      this.organiserRequestsError = "";
      try {
        const response = await apiClient.get("/admin/organiser-requests");
        this.organiserRequests = response.data.data;
      } catch (error) {
        this.organiserRequestsError = getApiError(
          error,
          "We couldn't load organiser requests right now. Please try again."
        ).message;
      } finally {
        this.isLoadingOrganiserRequests = false;
      }
    },

    async approveOrganiser(userId) {
      await apiClient.post(`/admin/organiser-requests/${userId}/approve`);
      this.organiserRequests = this.organiserRequests.filter((user) => user.id !== userId);
    },

    async rejectOrganiser(userId) {
      await apiClient.post(`/admin/organiser-requests/${userId}/reject`);
      this.organiserRequests = this.organiserRequests.filter((user) => user.id !== userId);
    },

    async fetchAuditLogs() {
      this.isLoadingAuditLogs = true;
      this.auditLogsError = "";
      try {
        const response = await apiClient.get("/admin/audit-logs");
        this.auditLogs = response.data.data;
      } catch (error) {
        this.auditLogsError = getApiError(error, "We couldn't load the audit log right now. Please try again.").message;
      } finally {
        this.isLoadingAuditLogs = false;
      }
    },

    async fetchAdmins() {
      this.isLoadingAdmins = true;
      this.adminsError = "";
      try {
        const response = await apiClient.get("/admin/admins");
        this.admins = response.data.data;
      } catch (error) {
        this.adminsError = getApiError(error, "We couldn't load admins right now. Please try again.").message;
      } finally {
        this.isLoadingAdmins = false;
      }
    },

    async createAdmin(payload) {
      const response = await apiClient.post("/admin/admins", payload);
      this.admins.push(response.data);
      return response.data;
    },

    async demoteAdmin(userId) {
      await apiClient.delete(`/admin/admins/${userId}`);
      this.admins = this.admins.filter((admin) => admin.id !== userId);
    },
  },
});
