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

    async register(eventId) {
      await apiClient.post(`/events/${eventId}/register`);
      this._setRegistered(eventId, true, 1);
    },

    async cancelRegistration(eventId) {
      await apiClient.delete(`/events/${eventId}/register`);
      this._setRegistered(eventId, false, -1);
    },

    _setRegistered(eventId, value, attendeesDelta) {
      // route params always arrive as strings, so compare numerically rather
      // than with strict equality against the numeric ids returned by the API.
      const id = Number(eventId);

      const event = this.events.find((event) => event.id === id);
      if (event) {
        event.is_registered = value;
        event.attendees_count += attendeesDelta;
      }
      if (this.currentEvent?.id === id) {
        this.currentEvent.is_registered = value;
        this.currentEvent.attendees_count += attendeesDelta;
      }
    },
  },
});
