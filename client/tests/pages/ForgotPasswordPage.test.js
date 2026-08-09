import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";

const post = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { post: (...args) => post(...args) },
}));

const { default: ForgotPasswordPage } = await import("../../src/pages/ForgotPasswordPage.vue");

function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: { template: "<div />" } },
      { path: "/forgot-password", component: ForgotPasswordPage },
      { path: "/login", component: { template: "<div />" } },
    ],
  });
}

async function mountPage() {
  const router = buildRouter();
  router.push("/forgot-password");
  await router.isReady();

  const wrapper = mount(ForgotPasswordPage, { global: { plugins: [router] } });
  return { wrapper, router };
}

describe("ForgotPasswordPage", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    post.mockReset();
  });

  it("shows the generic confirmation message after a successful submit", async () => {
    post.mockResolvedValue({
      data: { message: "If an account exists for that email, a password reset link is on its way." },
    });

    const { wrapper } = await mountPage();
    await wrapper.find("#email").setValue("lesley@example.com");
    await wrapper.find("form").trigger("submit.prevent");
    await flushPromises();

    expect(post).toHaveBeenCalledWith("/forgot-password", { email: "lesley@example.com" });
    expect(wrapper.text()).toContain("If an account exists for");
    expect(wrapper.text()).toContain("lesley@example.com");
  });

  it("shows an error message when the request fails", async () => {
    post.mockRejectedValue({ request: {} });

    const { wrapper } = await mountPage();
    await wrapper.find("#email").setValue("lesley@example.com");
    await wrapper.find("form").trigger("submit.prevent");
    await flushPromises();

    expect(wrapper.text()).toContain("having trouble reaching the server");
  });
});
