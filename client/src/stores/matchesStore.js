import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

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
        this.error = "We couldn't load your matches right now. Please try again.";
      } finally {
        this.isLoading = false;
      }
    },
  },
});
