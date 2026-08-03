import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { useEventsStore } from "./eventsStore.js";

export const useBookmarkStore = defineStore("bookmarks", {
  state: () => ({
    bookmarkedEvents: [],
    isLoading: false,
    error: "",
  }),

  actions: {
    async fetchBookmarks() {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get("/bookmarks");
        this.bookmarkedEvents = response.data.data;
      } catch (error) {
        this.error = "We couldn't load your saved events. Please try again.";
      } finally {
        this.isLoading = false;
      }
    },

    async toggleBookmark(eventId, isBookmarked) {
      const response = isBookmarked
        ? await apiClient.delete(`/bookmarks/${eventId}`)
        : await apiClient.post(`/bookmarks/${eventId}`);
      const updatedEvent = response.data.data;
      // Bookmark state also shows up on event cards owned by eventsStore
      // (the events list, current event, etc.), so keep those in sync too.
      useEventsStore()._applyEventUpdate(updatedEvent);

      if (!updatedEvent.is_bookmarked) {
        this.bookmarkedEvents = this.bookmarkedEvents.filter((event) => event.id !== updatedEvent.id);
      }
    },
  },
});
