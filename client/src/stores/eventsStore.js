import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useEventsStore = defineStore("events", {
  state: () => ({
    events: [],
    pagination: null,
    currentEvent: null,
    isLoading: false,
    error: "",
    myEvents: [],
    isLoadingMyEvents: false,
    myEventsError: "",
    recommendedEvents: [],
    isLoadingRecommended: false,
    recommendedError: "",
    organizedEvents: [],
    isLoadingOrganizedEvents: false,
    organizedEventsError: "",
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
        this.error = getApiError(error, "We couldn't load events right now. Please try again.").message;
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
        this.error = getApiError(error, "We couldn't load this event.").message;
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

    async fetchMyEvents() {
      this.isLoadingMyEvents = true;
      this.myEventsError = "";
      try {
        const response = await apiClient.get("/users/me/events");
        this.myEvents = response.data.data;
      } catch (error) {
        this.myEventsError = getApiError(error, "We couldn't load your registered events. Please try again.").message;
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
        this.recommendedError = getApiError(error, "We couldn't load recommended events. Please try again.").message;
      } finally {
        this.isLoadingRecommended = false;
      }
    },

    async fetchOrganizedEvents() {
      this.isLoadingOrganizedEvents = true;
      this.organizedEventsError = "";
      try {
        const response = await apiClient.get("/events", { params: { mine: 1 } });
        this.organizedEvents = response.data.data;
      } catch (error) {
        this.organizedEventsError = getApiError(error, "We couldn't load your events. Please try again.").message;
      } finally {
        this.isLoadingOrganizedEvents = false;
      }
    },

    async createEvent(payload) {
      const response = await apiClient.post("/events", payload);
      this.organizedEvents.unshift(response.data.data);
      return response.data.data;
    },

    async updateEvent(eventId, payload) {
      const response = await apiClient.patch(`/events/${eventId}`, payload);
      this._applyEventUpdate(response.data.data);
      const index = this.organizedEvents.findIndex((event) => event.id === response.data.data.id);
      if (index !== -1) {
        this.organizedEvents[index] = response.data.data;
      }
      return response.data.data;
    },

    async uploadCoverImage(eventId, file) {
      const formData = new FormData();
      formData.append("cover_image", file);
      const response = await apiClient.post(`/events/${eventId}/cover-image`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      this._applyEventUpdate(response.data.data);
      const index = this.organizedEvents.findIndex((event) => event.id === response.data.data.id);
      if (index !== -1) {
        this.organizedEvents[index] = response.data.data;
      }
      return response.data.data;
    },

    async deleteEvent(eventId) {
      await apiClient.delete(`/events/${eventId}`);
      this.organizedEvents = this.organizedEvents.filter((event) => event.id !== Number(eventId));
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
