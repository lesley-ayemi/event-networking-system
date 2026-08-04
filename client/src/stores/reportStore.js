import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

export const useReportStore = defineStore("reports", {
  actions: {
    async submitReport({ reportableType, reportableId, reason, details }) {
      const response = await apiClient.post("/reports", {
        reportable_type: reportableType,
        reportable_id: reportableId,
        reason,
        details: details || null,
      });
      return response.data;
    },
  },
});
