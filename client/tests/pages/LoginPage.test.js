import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";

const post = vi.fn();
const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    post: (...args) => post(...args),
    get: (...args) => get(...args),
  },
}));

const { default: LoginPage } = await import("../../src/pages/LoginPage.vue");

function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/login", component: LoginPage },
      { path: "/register", component: { template: "<div />" } },
      { path: "/forgot-password", component: { template: "<div />" } },
      { path: "/dashboard", component: { template: "<div />" } },
    ],
  });
}

async function mountLoginPage() {
  const router = buildRouter();
  router.push("/login");
  await router.isReady();

  const wrapper = mount(LoginPage, {
    global: { plugins: [router] },
  });
  return { wrapper, router };
}

describe("LoginPage", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    post.mockReset();
    get.mockReset();
  });

  it("logs in and redirects to the dashboard on success", async () => {
    post.mockResolvedValue({
      data: { user: { id: 1, first_name: "Lesley", email: "lesley@example.com" }, token: "token-1" },
    });

    const { wrapper, router } = await mountLoginPage();

    await wrapper.find("#email").setValue("lesley@example.com");
    await wrapper.find("#password").setValue("supersecret");
    await wrapper.find("form").trigger("submit.prevent");
    await flushPromises();

    expect(post).toHaveBeenCalledWith("/login", { email: "lesley@example.com", password: "supersecret" });
    expect(router.currentRoute.value.path).toBe("/dashboard");
  });

  it("shows an error message and stays on the page when login fails", async () => {
    post.mockRejectedValue({
      response: { data: { success: false, message: "These credentials do not match our records.", errorCode: "INVALID_CREDENTIALS" } },
    });

    const { wrapper, router } = await mountLoginPage();

    await wrapper.find("#email").setValue("lesley@example.com");
    await wrapper.find("#password").setValue("wrong-password");
    await wrapper.find("form").trigger("submit.prevent");
    await flushPromises();

    expect(wrapper.text()).toContain("These credentials do not match our records.");
    expect(router.currentRoute.value.path).toBe("/login");
  });

  it("links to the register and forgot-password pages", async () => {
    const { wrapper } = await mountLoginPage();

    const links = wrapper.findAll("a").map((a) => a.attributes("href"));
    expect(links).toContain("/register");
    expect(links).toContain("/forgot-password");
  });
});
