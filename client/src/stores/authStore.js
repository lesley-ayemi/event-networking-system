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

    // Runs once on app boot to rebuild the session from the stored token.
    // A 429 here (e.g. the user was rate-limited right before refreshing)
    // isn't proof the token is bad — retrying a couple of times with a short
    // backoff avoids bouncing someone with a perfectly valid session to
    // /login just because this one request landed in a busy window. Only a
    // real 401 means the token itself is invalid, so only that clears it.
    async restoreSession() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        return;
      }

      const maxAttempts = 3;
      for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        try {
          const response = await apiClient.get("/user");
          useUserStore().setUser(response.data);
          return;
        } catch (error) {
          if (error.response?.status === 401) {
            this._clearSession();
            return;
          }
          if (error.response?.status === 429 && attempt < maxAttempts) {
            await new Promise((resolve) => setTimeout(resolve, attempt * 1500));
            continue;
          }
          return;
        }
      }
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
