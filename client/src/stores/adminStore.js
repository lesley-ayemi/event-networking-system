import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useAdminStore = defineStore("admin", {
  state: () => ({
    reports: [],
    reportsPagination: null,
    isLoadingReports: false,
    reportsError: "",

    flaggedAccounts: [],
    isLoadingFlagged: false,
    flaggedError: "",

    organiserRequests: [],
    isLoadingOrganiserRequests: false,
    organiserRequestsError: "",

    auditLogs: [],
    auditLogsPagination: null,
    isLoadingAuditLogs: false,
    auditLogsError: "",

    admins: [],
    isLoadingAdmins: false,
    adminsError: "",

    users: [],
    usersPagination: null,
    isLoadingUsers: false,
    usersError: "",

    reportContext: [],
    reportContextReportId: null,
    isLoadingReportContext: false,
    reportContextError: "",

    currentUser: null,
    isLoadingCurrentUser: false,
    currentUserError: "",
  }),

  actions: {
    async fetchReports(filters = {}) {
      this.isLoadingReports = true;
      this.reportsError = "";
      try {
        const response = await apiClient.get("/admin/reports", { params: filters });
        this.reports = response.data.data;
        this.reportsPagination = response.data.meta;
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
      const response = await apiClient.post(`/admin/users/${userId}/suspend`);
      this.flaggedAccounts = this.flaggedAccounts.filter((user) => user.id !== userId);
      this._applyUserUpdate(response.data);
    },

    async unsuspendUser(userId) {
      const response = await apiClient.post(`/admin/users/${userId}/unsuspend`);
      this._applyUserUpdate(response.data);
    },

    async fetchUsers(filters = {}) {
      this.isLoadingUsers = true;
      this.usersError = "";
      try {
        const response = await apiClient.get("/admin/users", { params: filters });
        this.users = response.data.data;
        this.usersPagination = response.data.meta;
      } catch (error) {
        this.usersError = getApiError(error, "We couldn't load users right now. Please try again.").message;
      } finally {
        this.isLoadingUsers = false;
      }
    },

    async fetchUser(userId) {
      this.isLoadingCurrentUser = true;
      this.currentUserError = "";
      try {
        const response = await apiClient.get(`/admin/users/${userId}`);
        this.currentUser = response.data;
      } catch (error) {
        this.currentUserError = getApiError(error, "We couldn't load this user.").message;
      } finally {
        this.isLoadingCurrentUser = false;
      }
    },

    async updateUser(userId, payload) {
      const response = await apiClient.patch(`/admin/users/${userId}`, payload);
      this._applyUserUpdate(response.data);
      return response.data;
    },

    async deleteUser(userId) {
      await apiClient.delete(`/admin/users/${userId}`);
      this.users = this.users.filter((user) => user.id !== userId);
      if (this.currentUser?.id === userId) {
        this.currentUser = null;
      }
    },

    // suspend/unsuspend/updateUser all return the full updated user, so keep
    // whichever cached copies (list row, detail view) happen to be loaded in sync.
    _applyUserUpdate(updatedUser) {
      if (this.currentUser?.id === updatedUser.id) {
        this.currentUser = updatedUser;
      }
      const index = this.users.findIndex((user) => user.id === updatedUser.id);
      if (index !== -1) {
        this.users[index] = updatedUser;
      }
    },

    async removeEvent(eventId) {
      await apiClient.delete(`/admin/events/${eventId}`);
    },

    async updateEvent(eventId, payload) {
      const response = await apiClient.patch(`/admin/events/${eventId}`, payload);
      return response.data.data;
    },

    async fetchReportContext(reportId) {
      this.isLoadingReportContext = true;
      this.reportContextError = "";
      this.reportContextReportId = reportId;
      try {
        const response = await apiClient.get(`/admin/reports/${reportId}/context`);
        this.reportContext = response.data.data;
      } catch (error) {
        this.reportContextError = getApiError(
          error,
          "We couldn't load that conversation. Please try again."
        ).message;
      } finally {
        this.isLoadingReportContext = false;
      }
    },

    clearReportContext() {
      this.reportContext = [];
      this.reportContextReportId = null;
      this.reportContextError = "";
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

    async fetchAuditLogs(params = {}) {
      this.isLoadingAuditLogs = true;
      this.auditLogsError = "";
      try {
        const response = await apiClient.get("/admin/audit-logs", { params });
        this.auditLogs = response.data.data;
        this.auditLogsPagination = response.data.meta;
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
