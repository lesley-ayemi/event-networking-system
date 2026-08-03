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
      await apiClient.post("/logout");
      this._clearSession();
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
