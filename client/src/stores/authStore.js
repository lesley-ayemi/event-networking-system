import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { useUserStore } from "./userStore.js";

export const useAuthStore = defineStore("auth", {
  getters: {
    isAuthenticated: () => useUserStore().user !== null,
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
      // Always clear the local session, even if the request fails (e.g. the
      // token was already invalid) — a broken network call shouldn't strand
      // the user in a logged-in-looking state they can't get out of.
      try {
        await apiClient.post("/logout");
      } finally {
        this._clearSession();
      }
    },

    async forgotPassword(email) {
      const response = await apiClient.post("/forgot-password", { email });
      return response.data;
    },

    async resetPassword(payload) {
      const response = await apiClient.post("/reset-password", payload);
      return response.data;
    },

    async restoreSession() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        return;
      }
      const response = await apiClient.get("/user");
      useUserStore().setUser(response.data);
    },

    _setSession(user, token) {
      localStorage.setItem("authToken", token);
      useUserStore().setUser(user);
    },

    _clearSession() {
      localStorage.removeItem("authToken");
      useUserStore().clearUser();
    },
  },
});
