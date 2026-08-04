import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";

export const useFriendsStore = defineStore("friends", {
  state: () => ({
    friends: [],
    incomingRequests: [],
    outgoingRequests: [],
    isLoading: false,
    error: "",
  }),

  actions: {
    async fetchAll() {
      this.isLoading = true;
      this.error = "";
      try {
        const [friends, incoming, outgoing] = await Promise.all([
          apiClient.get("/friends"),
          apiClient.get("/friends/requests/incoming"),
          apiClient.get("/friends/requests/outgoing"),
        ]);
        this.friends = friends.data.data;
        this.incomingRequests = incoming.data.data;
        this.outgoingRequests = outgoing.data.data;
      } catch (error) {
        this.error = getApiError(error, "We couldn't load your friends right now. Please try again.").message;
      } finally {
        this.isLoading = false;
      }
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
