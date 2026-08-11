import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

// See conversationsStore's STALE_AFTER_MS comment — DefaultLayout re-mounts
// on every navigation and calls fetchAll() each time, so without a cache
// window this fires 3 requests (friends + incoming + outgoing) on every
// single page change.
const STALE_AFTER_MS = 30000;

export const useFriendsStore = defineStore("friends", {
  state: () => ({
    friends: [],
    incomingRequests: [],
    outgoingRequests: [],
    isLoading: false,
    error: "",
    lastFetchedAt: 0,
    _fetchPromise: null,
  }),

  actions: {
    // See conversationsStore's fetchConversations() comment — DefaultLayout
    // and the current page (e.g. FriendsPage, DashboardPage) both call this
    // in the same render tick, before either request resolves and updates
    // lastFetchedAt, so an in-flight promise has to be tracked too or both
    // slip past the freshness check.
    fetchAll() {
      if (this.lastFetchedAt && Date.now() - this.lastFetchedAt < STALE_AFTER_MS) {
        return Promise.resolve();
      }
      if (this._fetchPromise) {
        return this._fetchPromise;
      }
      this.isLoading = true;
      this.error = "";
      this._fetchPromise = Promise.all([
        apiClient.get("/friends"),
        apiClient.get("/friends/requests/incoming"),
        apiClient.get("/friends/requests/outgoing"),
      ])
        .then(([friends, incoming, outgoing]) => {
          this.friends = friends.data.data;
          this.incomingRequests = incoming.data.data;
          this.outgoingRequests = outgoing.data.data;
          this.lastFetchedAt = Date.now();
        })
        .catch((error) => {
          this.error = getApiError(error, "We couldn't load your friends right now. Please try again.").message;
        })
        .finally(() => {
          this.isLoading = false;
          this._fetchPromise = null;
        });
      return this._fetchPromise;
    },

    async sendFriendRequest(recipientId) {
      const response = await apiClient.post("/friends/requests", { recipient_id: recipientId });
      this.outgoingRequests.push(response.data);
    },

    async acceptRequest(friendRequestId) {
      const response = await apiClient.patch(`/friends/requests/${friendRequestId}/accept`);
      this.incomingRequests = this.incomingRequests.filter((request) => request.id !== friendRequestId);
      this.friends.push(response.data.sender);
    },

    async declineRequest(friendRequestId) {
      await apiClient.patch(`/friends/requests/${friendRequestId}/decline`);
      this.incomingRequests = this.incomingRequests.filter((request) => request.id !== friendRequestId);
    },

    async removeFriend(userId) {
      await apiClient.delete(`/friends/${userId}`);
      this.friends = this.friends.filter((friend) => friend.id !== userId);
    },

    async blockUser(userId) {
      await apiClient.post(`/blocks/${userId}`);
      this.friends = this.friends.filter((friend) => friend.id !== userId);
      this.incomingRequests = this.incomingRequests.filter((request) => request.sender.id !== userId);
      this.outgoingRequests = this.outgoingRequests.filter((request) => request.recipient.id !== userId);
    },
  },
});
