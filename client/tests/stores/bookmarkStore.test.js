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

const { useBookmarkStore } = await import("../../src/stores/bookmarkStore.js");
const { useEventsStore } = await import("../../src/stores/eventsStore.js");

describe("bookmarkStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
    del.mockReset();
  });

  it("fetchBookmarks() stores the saved events", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 2, name: "Design Mixer", is_bookmarked: true }] } });

    const store = useBookmarkStore();
    await store.fetchBookmarks();

    expect(get).toHaveBeenCalledWith("/bookmarks");
    expect(store.bookmarkedEvents).toEqual([{ id: 2, name: "Design Mixer", is_bookmarked: true }]);
  });

  it("fetchBookmarks() records an error on failure", async () => {
    get.mockRejectedValue(new Error("network error"));

    const store = useBookmarkStore();
    await store.fetchBookmarks();

    expect(store.error).toBe("We couldn't load your saved events. Please try again.");
  });

  it("toggleBookmark() saves an event via POST when currently unbookmarked, and syncs eventsStore", async () => {
    const updatedEvent = { id: 1, is_bookmarked: true };
    post.mockResolvedValue({ data: { data: updatedEvent } });

    const eventsStore = useEventsStore();
    eventsStore.events = [{ id: 1, is_bookmarked: false }];

    const store = useBookmarkStore();
    await store.toggleBookmark("1", false);

    expect(post).toHaveBeenCalledWith("/bookmarks/1");
    expect(del).not.toHaveBeenCalled();
    expect(eventsStore.events[0]).toEqual(updatedEvent);
  });

  it("toggleBookmark() removes an event via DELETE when currently bookmarked, and drops it from bookmarkedEvents", async () => {
    const updatedEvent = { id: 1, is_bookmarked: false };
    del.mockResolvedValue({ data: { data: updatedEvent } });

    const eventsStore = useEventsStore();
    eventsStore.events = [{ id: 1, is_bookmarked: true }];

    const store = useBookmarkStore();
    store.bookmarkedEvents = [{ id: 1, is_bookmarked: true }];

    await store.toggleBookmark("1", true);

    expect(del).toHaveBeenCalledWith("/bookmarks/1");
    expect(post).not.toHaveBeenCalled();
    expect(eventsStore.events[0]).toEqual(updatedEvent);
    expect(store.bookmarkedEvents).toEqual([]);
  });
});
