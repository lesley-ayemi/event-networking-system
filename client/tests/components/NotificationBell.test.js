import { describe, it, expect, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createRouter, createMemoryHistory } from "vue-router";
import NotificationBell from "../../src/components/NotificationBell.vue";
import { useConversationsStore } from "../../src/stores/conversationsStore.js";
import { useFriendsStore } from "../../src/stores/friendsStore.js";

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: "/", component: { template: "<div />" } },
    { path: "/messages", component: { template: "<div />" } },
    { path: "/friends", component: { template: "<div />" } },
  ],
});

describe("NotificationBell", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("labels itself plainly with no unread notifications", () => {
    const wrapper = mount(NotificationBell, { global: { plugins: [router] } });

    expect(wrapper.find("button").attributes("aria-label")).toBe("Notifications");
  });

  it("includes the unread count in its accessible name", () => {
    useConversationsStore().conversations = [{ id: 1, unread_count: 2 }];
    useFriendsStore().incomingRequests = [{ id: 1 }];

    const wrapper = mount(NotificationBell, { global: { plugins: [router] } });

    expect(wrapper.find("button").attributes("aria-label")).toBe("Notifications, 3 unread");
  });

  it("toggles aria-expanded when clicked", async () => {
    const wrapper = mount(NotificationBell, { global: { plugins: [router] } });
    const button = wrapper.find("button");

    expect(button.attributes("aria-expanded")).toBe("false");
    await button.trigger("click");
    expect(button.attributes("aria-expanded")).toBe("true");
  });
});
