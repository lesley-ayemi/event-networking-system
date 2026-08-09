import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import OnboardingProgressBar from "../../src/components/OnboardingProgressBar.vue";

describe("OnboardingProgressBar", () => {
  it("marks the current step with aria-current", () => {
    const wrapper = mount(OnboardingProgressBar, {
      props: { steps: ["Basics", "Preferences", "Comfort"], currentStep: 2 },
    });

    const steps = wrapper.findAll("li");
    expect(steps[0].attributes("aria-current")).toBeUndefined();
    expect(steps[1].attributes("aria-current")).toBe("step");
    expect(steps[2].attributes("aria-current")).toBeUndefined();
  });

  it("announces completed steps to assistive tech even though they only show a checkmark icon", () => {
    const wrapper = mount(OnboardingProgressBar, {
      props: { steps: ["Basics", "Preferences", "Comfort"], currentStep: 3 },
    });

    const steps = wrapper.findAll("li");
    expect(steps[0].text()).toContain("Completed");
    expect(steps[1].text()).toContain("Completed");
  });

  it("shows the plain step number for upcoming steps", () => {
    const wrapper = mount(OnboardingProgressBar, {
      props: { steps: ["Basics", "Preferences", "Comfort"], currentStep: 1 },
    });

    const steps = wrapper.findAll("li");
    expect(steps[1].text()).toContain("2");
    expect(steps[2].text()).toContain("3");
  });
});
