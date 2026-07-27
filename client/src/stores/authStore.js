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
