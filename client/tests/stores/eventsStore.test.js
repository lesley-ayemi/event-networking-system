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

  it("register() sends the matching answers and adopts the server's updated event", async () => {
    const answers = {
      interaction_mode: "one_to_one",
      open_to_matching: true,
      message_before_event: false,
      preferred_group_size: 4,
      attendance_format: "virtual",
    };
    const updatedEvent = { id: 1, is_registered: true, attendees_count: 4, my_registration: answers };
    post.mockResolvedValue({ data: { data: updatedEvent } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_registered: false, attendees_count: 3 }];
    store.currentEvent = { id: 1, is_registered: false, attendees_count: 3 };

    // route params always arrive as strings
    await store.register("1", answers);

    expect(post).toHaveBeenCalledWith("/events/1/register", answers);
    expect(store.events[0]).toEqual(updatedEvent);
    expect(store.currentEvent).toEqual(updatedEvent);
  });

  it("cancelRegistration() adopts the server's updated event", async () => {
    const updatedEvent = { id: 1, is_registered: false, attendees_count: 3, my_registration: null };
    del.mockResolvedValue({ data: { data: updatedEvent } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_registered: true, attendees_count: 4 }];
    store.currentEvent = { id: 1, is_registered: true, attendees_count: 4 };

    await store.cancelRegistration("1");

    expect(del).toHaveBeenCalledWith("/events/1/register");
    expect(store.events[0]).toEqual(updatedEvent);
    expect(store.currentEvent).toEqual(updatedEvent);
  });

  it("fetchMyEvents() stores the viewer's registered events", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 3, name: "Founders Mixer" }] } });

    const store = useEventsStore();
    await store.fetchMyEvents();

    expect(get).toHaveBeenCalledWith("/users/me/events");
    expect(store.myEvents).toEqual([{ id: 3, name: "Founders Mixer" }]);
  });

  it("fetchMyEvents() records an error on failure", async () => {
    get.mockRejectedValue(new Error("network error"));

    const store = useEventsStore();
    await store.fetchMyEvents();

    expect(store.myEventsError).toBe("We couldn't load your registered events. Please try again.");
  });

  it("fetchRecommendedEvents() filters out registered/bookmarked/past events and caps at 4", async () => {
    const future = new Date(Date.now() + 86400000).toISOString();
    const past = new Date(Date.now() - 86400000).toISOString();
    get.mockResolvedValue({
      data: {
        data: [
          { id: 1, starts_at: future, is_registered: false, is_bookmarked: false },
          { id: 2, starts_at: future, is_registered: true, is_bookmarked: false },
          { id: 3, starts_at: future, is_registered: false, is_bookmarked: true },
          { id: 4, starts_at: past, is_registered: false, is_bookmarked: false },
          { id: 5, starts_at: future, is_registered: false, is_bookmarked: false },
          { id: 6, starts_at: future, is_registered: false, is_bookmarked: false },
          { id: 7, starts_at: future, is_registered: false, is_bookmarked: false },
        ],
      },
    });

    const store = useEventsStore();
    await store.fetchRecommendedEvents("Technology");

    expect(get).toHaveBeenCalledWith("/events", { params: { industry: "Technology" } });
    expect(store.recommendedEvents.map((event) => event.id)).toEqual([1, 5, 6, 7]);
  });

  it("fetchRecommendedEvents() omits the industry param when none is given", async () => {
    get.mockResolvedValue({ data: { data: [] } });

    const store = useEventsStore();
    await store.fetchRecommendedEvents();

    expect(get).toHaveBeenCalledWith("/events", { params: {} });
  });
});
