import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

export const useEventsStore = defineStore("events", {
  state: () => ({
    events: [],
    pagination: null,
    currentEvent: null,
    isLoading: false,
    error: "",
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

    // register/cancel return the full updated event, so the store adopts
    // that as the source of truth instead of patching fields by hand.
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
