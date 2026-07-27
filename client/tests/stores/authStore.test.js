import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const post = vi.fn();
const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { post: (...args) => post(...args), get: (...args) => get(...args) },
}));

const { useAuthStore } = await import("../../src/stores/authStore.js");

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
    expect(store.user).toBeNull();
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
    expect(store.user.email).toBe("lesley@example.com");
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
    expect(store.user).toBeNull();
    expect(localStorage.getItem("authToken")).toBeNull();
  });

  it("restoreSession() re-hydrates from a stored token", async () => {
    localStorage.setItem("authToken", "existing-token");
    get.mockResolvedValue({ data: { id: 4, email: "restored@example.com" } });

    const store = useAuthStore();
    await store.restoreSession();

    expect(get).toHaveBeenCalledWith("/user");
    expect(store.isAuthenticated).toBe(true);
    expect(store.user.email).toBe("restored@example.com");
  });

  it("restoreSession() does nothing when there is no stored token", async () => {
    const store = useAuthStore();
    await store.restoreSession();

    expect(get).not.toHaveBeenCalled();
    expect(store.isAuthenticated).toBe(false);
  });
});
