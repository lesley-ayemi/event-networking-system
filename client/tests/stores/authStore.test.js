import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const post = vi.fn();
const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    post: (...args) => post(...args),
    get: (...args) => get(...args),
  },
}));

const { useAuthStore } = await import("../../src/stores/authStore.js");
const { useUserStore } = await import("../../src/stores/userStore.js");

describe("authStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    post.mockReset();
    get.mockReset();
  });

  it("starts unauthenticated", () => {
    const store = useAuthStore();
    expect(store.isAuthenticated).toBe(false);
    expect(useUserStore().user).toBeNull();
  });

  it("register() stores the user and token", async () => {
    post.mockResolvedValue({
      data: { user: { id: 1, first_name: "Lesley", email: "lesley@example.com" }, token: "token-1" },
    });

    const store = useAuthStore();
    await store.register({
      first_name: "Lesley",
      last_name: "Ayemi",
      email: "lesley@example.com",
      password: "supersecret",
      password_confirmation: "supersecret",
    });

    expect(post).toHaveBeenCalledWith("/register", {
      first_name: "Lesley",
      last_name: "Ayemi",
      email: "lesley@example.com",
      password: "supersecret",
      password_confirmation: "supersecret",
    });
    expect(store.isAuthenticated).toBe(true);
    expect(useUserStore().user.email).toBe("lesley@example.com");
    expect(localStorage.getItem("authToken")).toBe("token-1");
  });

  it("login() stores the user and token", async () => {
    post.mockResolvedValue({
      data: { user: { id: 2, first_name: "Returning", email: "returning@example.com" }, token: "token-2" },
    });

    const store = useAuthStore();
    await store.login({ email: "returning@example.com", password: "supersecret" });

    expect(post).toHaveBeenCalledWith("/login", {
      email: "returning@example.com",
      password: "supersecret",
    });
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem("authToken")).toBe("token-2");
  });

  it("logout() clears state and calls the API", async () => {
    post.mockResolvedValueOnce({
      data: { user: { id: 3, email: "someone@example.com" }, token: "token-3" },
    });
    post.mockResolvedValueOnce({ data: { message: "Logged out." } });

    const store = useAuthStore();
    await store.login({ email: "someone@example.com", password: "supersecret" });
    await store.logout();

    expect(post).toHaveBeenCalledWith("/logout");
    expect(store.isAuthenticated).toBe(false);
    expect(useUserStore().user).toBeNull();
    expect(localStorage.getItem("authToken")).toBeNull();
  });

  it("restoreSession() re-hydrates from a stored token", async () => {
    localStorage.setItem("authToken", "existing-token");
    get.mockResolvedValue({ data: { id: 4, email: "restored@example.com" } });

    const store = useAuthStore();
    await store.restoreSession();

    expect(get).toHaveBeenCalledWith("/user");
    expect(store.isAuthenticated).toBe(true);
    expect(useUserStore().user.email).toBe("restored@example.com");
  });

  it("restoreSession() does nothing when there is no stored token", async () => {
    const store = useAuthStore();
    await store.restoreSession();

    expect(get).not.toHaveBeenCalled();
    expect(store.isAuthenticated).toBe(false);
  });

  it("forgotPassword() posts the email and returns the response", async () => {
    post.mockResolvedValue({ data: { message: "If an account exists for that email, a password reset link is on its way." } });

    const store = useAuthStore();
    const result = await store.forgotPassword("lesley@example.com");

    expect(post).toHaveBeenCalledWith("/forgot-password", { email: "lesley@example.com" });
    expect(result.message).toBe("If an account exists for that email, a password reset link is on its way.");
  });

  it("resetPassword() posts the token/email/password payload", async () => {
    post.mockResolvedValue({ data: { message: "Your password has been reset. You can now log in." } });

    const store = useAuthStore();
    const payload = {
      token: "abc123",
      email: "lesley@example.com",
      password: "new-password",
      password_confirmation: "new-password",
    };
    const result = await store.resetPassword(payload);

    expect(post).toHaveBeenCalledWith("/reset-password", payload);
    expect(result.message).toBe("Your password has been reset. You can now log in.");
  });
});
