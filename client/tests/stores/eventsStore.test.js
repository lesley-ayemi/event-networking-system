import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const get = vi.fn();
const post = vi.fn();
const del = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    get: (...args) => get(...args),
    post: (...args) => post(...args),
    delete: (...args) => del(...args),
  },
}));

const { useEventsStore } = await import("../../src/stores/eventsStore.js");

describe("eventsStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
    del.mockReset();
  });

  it("fetchEvents() stores the events and pagination meta", async () => {
    get.mockResolvedValue({
      data: {
        data: [{ id: 1, name: "Founders Mixer" }],
        meta: { current_page: 1, last_page: 1 },
      },
    });

    const store = useEventsStore();
    await store.fetchEvents({ industry: "Technology" });

    expect(get).toHaveBeenCalledWith("/events", { params: { industry: "Technology" } });
    expect(store.events).toEqual([{ id: 1, name: "Founders Mixer" }]);
    expect(store.pagination).toEqual({ current_page: 1, last_page: 1 });
  });

  it("fetchEvents() records an error on failure", async () => {
    get.mockRejectedValue(new Error("network error"));

    const store = useEventsStore();
    await store.fetchEvents();

    expect(store.error).toBe("We couldn't load events right now. Please try again.");
    expect(store.events).toEqual([]);
  });

  it("fetchEvent() stores the current event", async () => {
    get.mockResolvedValue({ data: { data: { id: 5, name: "Design Mixer" } } });

    const store = useEventsStore();
    await store.fetchEvent(5);

    expect(get).toHaveBeenCalledWith("/events/5");
    expect(store.currentEvent).toEqual({ id: 5, name: "Design Mixer" });
  });

  it("register() marks the event as registered in the list and current event", async () => {
    post.mockResolvedValue({ data: { message: "You are registered for this event." } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_registered: false, attendees_count: 3 }];
    store.currentEvent = { id: 1, is_registered: false, attendees_count: 3 };

    // route params always arrive as strings
    await store.register("1");

    expect(post).toHaveBeenCalledWith("/events/1/register");
    expect(store.events[0].is_registered).toBe(true);
    expect(store.events[0].attendees_count).toBe(4);
    expect(store.currentEvent.is_registered).toBe(true);
    expect(store.currentEvent.attendees_count).toBe(4);
  });

  it("cancelRegistration() unmarks the event as registered", async () => {
    del.mockResolvedValue({ data: { message: "Your registration has been cancelled." } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_registered: true, attendees_count: 4 }];
    store.currentEvent = { id: 1, is_registered: true, attendees_count: 4 };

    await store.cancelRegistration("1");

    expect(del).toHaveBeenCalledWith("/events/1/register");
    expect(store.events[0].is_registered).toBe(false);
    expect(store.events[0].attendees_count).toBe(3);
    expect(store.currentEvent.is_registered).toBe(false);
    expect(store.currentEvent.attendees_count).toBe(3);
  });
});
