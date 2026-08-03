import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

export const useEventsStore = defineStore("events", {
  state: () => ({
    events: [],
    pagination: null,
    currentEvent: null,
    isLoading: false,
    error: "",
    bookmarkedEvents: [],
    isLoadingBookmarks: false,
    bookmarksError: "",
    myEvents: [],
    isLoadingMyEvents: false,
    myEventsError: "",
    recommendedEvents: [],
    isLoadingRecommended: false,
    recommendedError: "",
  }),

  actions: {
    async fetchEvents(filters = {}) {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get("/events", { params: filters });
        this.events = response.data.data;
        this.pagination = response.data.meta;
      } catch (error) {
        this.error = "We couldn't load events right now. Please try again.";
      } finally {
        this.isLoading = false;
      }
    },

    async fetchEvent(id) {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get(`/events/${id}`);
        this.currentEvent = response.data.data;
      } catch (error) {
        this.error = "We couldn't load this event.";
      } finally {
        this.isLoading = false;
      }
    },

    async register(eventId, answers) {
      const response = await apiClient.post(`/events/${eventId}/register`, answers);
      this._applyEventUpdate(response.data.data);
    },

    async cancelRegistration(eventId) {
      const response = await apiClient.delete(`/events/${eventId}/register`);
      this._applyEventUpdate(response.data.data);
    },

    async fetchBookmarks() {
      this.isLoadingBookmarks = true;
      this.bookmarksError = "";
      try {
        const response = await apiClient.get("/bookmarks");
        this.bookmarkedEvents = response.data.data;
      } catch (error) {
        this.bookmarksError = "We couldn't load your saved events. Please try again.";
      } finally {
        this.isLoadingBookmarks = false;
      }
    },

    async toggleBookmark(eventId, isBookmarked) {
      const response = isBookmarked
        ? await apiClient.delete(`/bookmarks/${eventId}`)
        : await apiClient.post(`/bookmarks/${eventId}`);
      const updatedEvent = response.data.data;
      this._applyEventUpdate(updatedEvent);

      if (!updatedEvent.is_bookmarked) {
        this.bookmarkedEvents = this.bookmarkedEvents.filter((event) => event.id !== updatedEvent.id);
      }
    },

    async fetchMyEvents() {
      this.isLoadingMyEvents = true;
      this.myEventsError = "";
      try {
        const response = await apiClient.get("/users/me/events");
        this.myEvents = response.data.data;
      } catch (error) {
        this.myEventsError = "We couldn't load your registered events. Please try again.";
      } finally {
        this.isLoadingMyEvents = false;
      }
    },

    // "Recommended" is intentionally simple for the dashboard: events in the
    // viewer's industry they haven't already registered for or saved, rather
    // than a separate scoring model.
    async fetchRecommendedEvents(industry) {
      this.isLoadingRecommended = true;
      this.recommendedError = "";
      try {
        const response = await apiClient.get("/events", { params: industry ? { industry } : {} });
        const now = new Date();
        this.recommendedEvents = response.data.data
          .filter((event) => !event.is_registered && !event.is_bookmarked && new Date(event.starts_at) >= now)
          .slice(0, 4);
      } catch (error) {
        this.recommendedError = "We couldn't load recommended events. Please try again.";
      } finally {
        this.isLoadingRecommended = false;
      }
    },

    // register/cancel/bookmark actions all return the full updated event, so
    // the store adopts that as the source of truth instead of patching fields
    // by hand.
    _applyEventUpdate(updatedEvent) {
      const index = this.events.findIndex((event) => event.id === updatedEvent.id);
      if (index !== -1) {
        this.events[index] = updatedEvent;
      }
      if (this.currentEvent?.id === updatedEvent.id) {
        this.currentEvent = updatedEvent;
      }
    },
  },
});
