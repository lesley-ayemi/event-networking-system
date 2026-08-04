import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useMatchesStore = defineStore("matches", {
  state: () => ({
    matches: [],
    isLoading: false,
    error: "",
  }),

  actions: {
    async fetchMatches() {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get("/matches");
        this.matches = response.data.data;
      } catch (error) {
        this.error = getApiError(error, "We couldn't load your matches right now. Please try again.").message;
      } finally {
        this.isLoading = false;
      }
    },
  },
});
