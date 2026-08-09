import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useUsersStore = defineStore("users", {
  state: () => ({
    viewedProfile: null,
    isLoadingProfile: false,
    profileError: "",
  }),

  actions: {
    async fetchUserProfile(userId) {
      this.isLoadingProfile = true;
      this.profileError = "";
      this.viewedProfile = null;
      try {
        const response = await apiClient.get(`/users/${userId}`);
        this.viewedProfile = response.data.data;
      } catch (error) {
        this.profileError = getApiError(error, "We couldn't load that profile. Please try again.").message;
      } finally {
        this.isLoadingProfile = false;
      }
    },
  },
});
