import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import Select from "../../src/components/Select.vue";

function mountSelect(props = {}) {
  return mount(Select, {
    props: { modelValue: "b", ...props },
    slots: {
      default: `
        <option value="a">Option A</option>
        <option value="b">Option B</option>
        <option value="c">Option C</option>
      `,
    },
  });
}

describe("Select", () => {
  it("renders the slotted options and reflects the selected value", () => {
    const wrapper = mountSelect();
    const options = wrapper.findAll("option");
    expect(options).toHaveLength(3);
    expect(wrapper.find("select").element.value).toBe("b");
  });

  it("emits update:modelValue with the new value when changed", async () => {
    const wrapper = mountSelect();
    await wrapper.find("select").setValue("c");

    expect(wrapper.emitted("update:modelValue")).toEqual([["c"]]);
  });

  it("forwards attrs like id and disabled onto the underlying select", () => {
    const wrapper = mountSelect({ id: "my-select", disabled: true });
    const select = wrapper.find("select");

    expect(select.attributes("id")).toBe("my-select");
    expect(select.element.disabled).toBe(true);
  });

  it("applies dark-mode styling classes when the dark prop is set", () => {
    const light = mountSelect({ dark: false });
    const dark = mountSelect({ dark: true });

    expect(light.find("select").classes()).not.toContain("bg-white/10");
    expect(dark.find("select").classes()).toContain("bg-white/10");
  });
});
