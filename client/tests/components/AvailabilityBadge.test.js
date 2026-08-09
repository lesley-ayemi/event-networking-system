import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AvailabilityBadge from "../../src/components/AvailabilityBadge.vue";

describe("AvailabilityBadge", () => {
  it("renders nothing for an unknown/empty status", () => {
    const wrapper = mount(AvailabilityBadge, { props: { status: "" } });
    expect(wrapper.find("span").exists()).toBe(false);
  });

  it("renders the label without a staleness suffix when no updatedAt is given", () => {
    const wrapper = mount(AvailabilityBadge, { props: { status: "available" } });
    expect(wrapper.text()).toBe("Available to chat");
  });

  it("renders normally (not stale) when updatedAt is recent", () => {
    const wrapper = mount(AvailabilityBadge, {
      props: { status: "available", updatedAt: new Date().toISOString() },
    });
    expect(wrapper.text()).toBe("Available to chat");
    expect(wrapper.find("span").classes()).toContain("bg-green-50");
  });

  it("shows a muted, aged badge when updatedAt is older than the staleness threshold", () => {
    const fifteenDaysAgo = new Date(Date.now() - 15 * 24 * 60 * 60 * 1000).toISOString();
    const wrapper = mount(AvailabilityBadge, {
      props: { status: "available", updatedAt: fifteenDaysAgo },
    });

    expect(wrapper.text()).toContain("Available to chat");
    expect(wrapper.text()).toContain("15d ago");
    expect(wrapper.find("span").classes()).toContain("bg-gray-100");
    expect(wrapper.find("span").classes()).not.toContain("bg-green-50");
  });

  it("sets a title tooltip describing when the status was set", () => {
    const fifteenDaysAgo = new Date(Date.now() - 15 * 24 * 60 * 60 * 1000).toISOString();
    const wrapper = mount(AvailabilityBadge, {
      props: { status: "available", updatedAt: fifteenDaysAgo },
    });

    expect(wrapper.find("span").attributes("title")).toBe("Status set 15d ago");
  });
});
