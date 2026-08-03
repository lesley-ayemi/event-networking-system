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

  it("fetchBookmarks() stores the saved events", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 2, name: "Design Mixer", is_bookmarked: true }] } });

    const store = useEventsStore();
    await store.fetchBookmarks();

    expect(get).toHaveBeenCalledWith("/bookmarks");
    expect(store.bookmarkedEvents).toEqual([{ id: 2, name: "Design Mixer", is_bookmarked: true }]);
  });

  it("toggleBookmark() saves an event via POST when currently unbookmarked", async () => {
    const updatedEvent = { id: 1, is_bookmarked: true };
    post.mockResolvedValue({ data: { data: updatedEvent } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_bookmarked: false }];

    await store.toggleBookmark("1", false);

    expect(post).toHaveBeenCalledWith("/bookmarks/1");
    expect(del).not.toHaveBeenCalled();
    expect(store.events[0]).toEqual(updatedEvent);
  });

  it("toggleBookmark() removes an event via DELETE when currently bookmarked, and drops it from bookmarkedEvents", async () => {
    const updatedEvent = { id: 1, is_bookmarked: false };
    del.mockResolvedValue({ data: { data: updatedEvent } });

    const store = useEventsStore();
    store.events = [{ id: 1, is_bookmarked: true }];
    store.bookmarkedEvents = [{ id: 1, is_bookmarked: true }];

    await store.toggleBookmark("1", true);

    expect(del).toHaveBeenCalledWith("/bookmarks/1");
    expect(post).not.toHaveBeenCalled();
    expect(store.events[0]).toEqual(updatedEvent);
    expect(store.bookmarkedEvents).toEqual([]);
  });
});
