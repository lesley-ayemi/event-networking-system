import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const post = vi.fn();
const patch = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    post: (...args) => post(...args),
    patch: (...args) => patch(...args),
  },
}));

const { useUserStore } = await import("../../src/stores/userStore.js");

describe("userStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    post.mockReset();
    patch.mockReset();
  });

  it("starts with no user", () => {
    const store = useUserStore();
    expect(store.user).toBeNull();
  });

  it("setUser()/clearUser() control the current user", () => {
    const store = useUserStore();
    store.setUser({ id: 1, email: "lesley@example.com" });
    expect(store.user.email).toBe("lesley@example.com");

    store.clearUser();
    expect(store.user).toBeNull();
  });

  it("updateProfile() sends the payload and stores the returned user", async () => {
    patch.mockResolvedValue({
      data: { id: 1, email: "lesley@example.com", job_title: "Product Designer" },
    });

    const store = useUserStore();
    await store.updateProfile({ job_title: "Product Designer" });

    expect(patch).toHaveBeenCalledWith("/profile", { job_title: "Product Designer" });
    expect(store.user.job_title).toBe("Product Designer");
  });

  it("uploadProfilePhoto() sends multipart form data and stores the returned user", async () => {
    post.mockResolvedValue({
      data: { id: 1, email: "lesley@example.com", profile_image: "http://localhost:8000/storage/profile-photos/a.jpg" },
    });

    const store = useUserStore();
    const file = new File(["fake-bytes"], "avatar.jpg", { type: "image/jpeg" });
    await store.uploadProfilePhoto(file);

    expect(post).toHaveBeenCalledWith(
      "/profile/photo",
      expect.any(FormData),
      { headers: { "Content-Type": "multipart/form-data" } }
    );
    expect(store.user.profile_image).toBe("http://localhost:8000/storage/profile-photos/a.jpg");
  });

  it("updateQuizAnswers() sends the answers and stores the returned user", async () => {
    const answers = { oneToOnePreference: 5, preferredGroupSize: 2 };
    patch.mockResolvedValue({
      data: { id: 1, email: "lesley@example.com", quiz_answers: answers },
    });

    const store = useUserStore();
    await store.updateQuizAnswers(answers);

    expect(patch).toHaveBeenCalledWith("/quiz", answers);
    expect(store.user.quiz_answers).toEqual(answers);
  });
});
