import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

// Firebase apps typically split the Auth user (uid/email) from a separate
// Firestore profile document. Laravel/Sanctum has just one User model, so
// this store owns that profile data while authStore owns session lifecycle.
export const useUserStore = defineStore("user", {
  state: () => ({
    user: null,
  }),

  actions: {
    setUser(user) {
      this.user = user;
    },

    clearUser() {
      this.user = null;
    },

    async updateProfile(payload) {
      const response = await apiClient.patch("/profile", payload);
      this.user = response.data;
    },

    async uploadProfilePhoto(file) {
      const formData = new FormData();
      formData.append("photo", file);
      const response = await apiClient.post("/profile/photo", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      this.user = response.data;
    },

    async updateQuizAnswers(answers) {
      const response = await apiClient.patch("/quiz", answers);
      this.user = response.data;
    },

    async requestOrganiserStatus() {
      const response = await apiClient.post("/organiser-requests");
      this.user = response.data;
    },
  },
});
