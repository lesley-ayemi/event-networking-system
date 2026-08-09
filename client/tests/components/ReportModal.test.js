import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";

const post = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { post: (...args) => post(...args) },
}));

const { default: ReportModal } = await import("../../src/components/ReportModal.vue");

function mountModal() {
  return mount(ReportModal, {
    props: { title: "Report Sam Rivera", reportableType: "user", reportableId: 5 },
  });
}

describe("ReportModal", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    post.mockReset();
  });

  it("exposes dialog semantics linked to its heading", () => {
    const wrapper = mountModal();
    const dialog = wrapper.find('[role="dialog"]');

    expect(dialog.exists()).toBe(true);
    expect(dialog.attributes("aria-modal")).toBe("true");
    const labelledby = dialog.attributes("aria-labelledby");
    expect(wrapper.find(`#${labelledby}`).text()).toBe("Report Sam Rivera");
  });

  it("emits close when Escape is pressed", async () => {
    const wrapper = mountModal();

    await wrapper.find('[role="dialog"]').trigger("keydown.esc");

    expect(wrapper.emitted("close")).toBeTruthy();
  });

  it("emits close when Cancel is clicked", async () => {
    const wrapper = mountModal();

    const cancelButton = wrapper.findAll("button").find((button) => button.text() === "Cancel");
    await cancelButton.trigger("click");

    expect(wrapper.emitted("close")).toBeTruthy();
  });
});
