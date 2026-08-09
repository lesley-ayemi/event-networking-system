import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";

const get = vi.fn();
const post = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    get: (...args) => get(...args),
    post: (...args) => post(...args),
  },
}));
vi.mock("../../src/services/echo.js", () => ({
  echo: {
    private: () => ({ listen: () => {} }),
    leave: () => {},
    socketId: () => "socket-123",
  },
}));

const { default: UserProfilePage } = await import("../../src/pages/UserProfilePage.vue");

function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/dashboard", component: { template: "<div />" } },
      { path: "/events", component: { template: "<div />" } },
      { path: "/saved-events", component: { template: "<div />" } },
      { path: "/matches", component: { template: "<div />" } },
      { path: "/friends", component: { template: "<div />" } },
      { path: "/messages", component: { template: "<div />" } },
      { path: "/profile", component: { template: "<div />" } },
      { path: "/users/:id", component: UserProfilePage },
      { path: "/messages/:conversationId", component: { template: "<div />" } },
    ],
  });
}

async function mountPage(id = "5") {
  get.mockImplementation((url) => {
    if (url === `/users/${id}`) {
      return Promise.resolve({
        data: {
          data: {
            id: Number(id),
            first_name: "Sam",
            last_name: "Rivera",
            bio: "Product designer who loves board games.",
            job_title: "Product Designer",
            industry: "Technology",
            networking_goals: "Meet other designers",
            profile_image: null,
            availability_status: "available",
          },
        },
      });
    }
    // DefaultLayout's onMounted hooks (conversations/friends) hit other
    // endpoints; a harmless empty list keeps them from throwing.
    return Promise.resolve({ data: { data: [] } });
  });

  const router = buildRouter();
  router.push(`/users/${id}`);
  await router.isReady();

  const wrapper = mount(UserProfilePage, { global: { plugins: [router] } });
  await flushPromises();
  return { wrapper, router };
}

describe("UserProfilePage", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
  });

  it("renders the viewed user's bio, job title, and networking goals", async () => {
    const { wrapper } = await mountPage();

    expect(get).toHaveBeenCalledWith("/users/5");
    expect(wrapper.text()).toContain("Sam Rivera");
    expect(wrapper.text()).toContain("Product Designer · Technology");
    expect(wrapper.text()).toContain("Product designer who loves board games.");
    expect(wrapper.text()).toContain("Meet other designers");
  });

  it("shows an error message when the profile can't be loaded", async () => {
    get.mockImplementation((url) => {
      if (url === "/users/999") {
        return Promise.reject({
          response: { data: { success: false, message: "We couldn't find that profile.", errorCode: "USER_NOT_FOUND" } },
        });
      }
      return Promise.resolve({ data: { data: [] } });
    });

    const router = buildRouter();
    router.push("/users/999");
    await router.isReady();
    const wrapper = mount(UserProfilePage, { global: { plugins: [router] } });
    await flushPromises();

    expect(wrapper.text()).toContain("We couldn't find that profile.");
  });

  it("starts a conversation and navigates to it when Message is clicked", async () => {
    post.mockResolvedValue({ data: { id: 42, other_user: { id: 5 } } });

    const { wrapper, router } = await mountPage();
    const messageButton = wrapper.findAll("button").find((button) => button.text() === "Message");
    await messageButton.trigger("click");
    await flushPromises();

    expect(post).toHaveBeenCalledWith("/conversations", { recipient_id: 5 });
    expect(router.currentRoute.value.path).toBe("/messages/42");
  });
});
