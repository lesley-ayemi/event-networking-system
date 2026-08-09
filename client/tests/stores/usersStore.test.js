import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { get: (...args) => get(...args) },
}));

const { useUsersStore } = await import("../../src/stores/usersStore.js");

describe("usersStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
  });

  it("fetchUserProfile() stores the viewed profile", async () => {
    get.mockResolvedValue({ data: { data: { id: 5, first_name: "Sam", last_name: "Rivera" } } });

    const store = useUsersStore();
    await store.fetchUserProfile(5);

    expect(get).toHaveBeenCalledWith("/users/5");
    expect(store.viewedProfile).toEqual({ id: 5, first_name: "Sam", last_name: "Rivera" });
  });

  it("fetchUserProfile() records an error and clears the profile on failure", async () => {
    get.mockRejectedValue({
      response: { data: { success: false, message: "We couldn't find that profile.", errorCode: "USER_NOT_FOUND" } },
    });

    const store = useUsersStore();
    store.viewedProfile = { id: 1, first_name: "Stale" };
    await store.fetchUserProfile(999);

    expect(store.profileError).toBe("We couldn't find that profile.");
    expect(store.viewedProfile).toBeNull();
  });
});
