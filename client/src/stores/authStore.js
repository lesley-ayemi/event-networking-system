import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => state.user !== null,
  },

  actions: {
    async register(payload) {
      const response = await apiClient.post("/register", payload);
      this._setSession(response.data.user, response.data.token);
    },

    async login(payload) {
      const response = await apiClient.post("/login", payload);
      this._setSession(response.data.user, response.data.token);
    },

    async logout() {
      await apiClient.post("/logout");
      this._clearSession();
    },

    async restoreSession() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        return;
      }
      const response = await apiClient.get("/user");
      this.user = response.data;
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

    _setSession(user, token) {
      localStorage.setItem("authToken", token);
      this.user = user;
    },

    _clearSession() {
      localStorage.removeItem("authToken");
      this.user = null;
    },
  },
});
