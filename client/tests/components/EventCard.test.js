import { describe, it, expect, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import EventCard from "../../src/components/EventCard.vue";

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: "/", component: { template: "<div />" } },
    { path: "/events/:id", component: { template: "<div />" } },
  ],
});

async function mountCard(event) {
  return mount(EventCard, {
    props: { event },
    global: { plugins: [router] },
  });
}

describe("EventCard", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders the event name, formatted date, and location", async () => {
    const wrapper = await mountCard({
      id: 1,
      name: "Founders Mixer",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: false,
      location: "Austin, TX",
      attendees_count: 4,
    });

    expect(wrapper.text()).toContain("Founders Mixer");
    expect(wrapper.text()).toContain("Austin, TX");
    expect(wrapper.text()).toContain("4 attending");
    expect(wrapper.text()).toContain("In person");
  });

  it("shows 'Virtual' and 'Location TBA' fallback appropriately", async () => {
    const wrapper = await mountCard({
      id: 2,
      name: "Remote Meetup",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: true,
      location: null,
      attendees_count: 0,
    });

    expect(wrapper.text()).toContain("Virtual");
    expect(wrapper.text()).not.toContain("Location TBA");
  });

  it("falls back to 'Location TBA' for an in-person event with no location set", async () => {
    const wrapper = await mountCard({
      id: 3,
      name: "Mystery Meetup",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: false,
      location: null,
      attendees_count: 0,
    });

    expect(wrapper.text()).toContain("Location TBA");
  });

  it("links 'View details' to the event's detail page", async () => {
    const wrapper = await mountCard({
      id: 42,
      name: "Founders Mixer",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: false,
      location: "Austin, TX",
      attendees_count: 4,
    });

    const link = wrapper.find("a");
    expect(link.text()).toBe("View details");
    expect(link.attributes("href")).toBe("/events/42");
  });

  it("shows interaction mode badges only when the event offers them", async () => {
    const withModes = await mountCard({
      id: 1,
      name: "Founders Mixer",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: false,
      location: "Austin, TX",
      attendees_count: 4,
      one_to_one_available: true,
      small_group_available: true,
    });
    expect(withModes.text()).toContain("One-to-one");
    expect(withModes.text()).toContain("Small group");

    const withoutModes = await mountCard({
      id: 5,
      name: "Quiet Mixer",
      starts_at: "2026-09-15T18:00:00Z",
      is_virtual: false,
      location: "Austin, TX",
      attendees_count: 4,
      one_to_one_available: false,
      small_group_available: false,
    });
    expect(withoutModes.text()).not.toContain("One-to-one");
    expect(withoutModes.text()).not.toContain("Small group");
  });
});
