import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { echo } from "../services/echo.js";

export const useConversationsStore = defineStore("conversations", {
  state: () => ({
    conversations: [],
    currentConversation: null,
    messages: [],
    isLoading: false,
    error: "",
    subscribedConversationId: null,
  }),

  getters: {
    unreadTotal: (state) => state.conversations.reduce((total, conversation) => total + conversation.unread_count, 0),
  },

  actions: {
    async fetchConversations() {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get("/conversations");
        this.conversations = response.data.data;
      } catch (error) {
        this.error = "We couldn't load your conversations right now. Please try again.";
      } finally {
        this.isLoading = false;
      }
    },

    async startConversation(recipientId) {
      const response = await apiClient.post("/conversations", { recipient_id: recipientId });
      this._upsertConversation(response.data);
      return response.data;
    },

    async fetchConversation(conversationId) {
      this.isLoading = true;
      this.error = "";
      try {
        const response = await apiClient.get(`/conversations/${conversationId}`);
        this.currentConversation = response.data;
      } catch (error) {
        this.error = "We couldn't load this conversation.";
      } finally {
        this.isLoading = false;
      }
    },

    async fetchMessages(conversationId) {
      const response = await apiClient.get(`/conversations/${conversationId}/messages`);
      this.messages = response.data.data;
    },

    async sendMessage(conversationId, body) {
      const response = await apiClient.post(`/conversations/${conversationId}/messages`, { body });
      this.messages.push(response.data);
      this._touchConversation(conversationId, response.data);
    },

    async markRead(conversationId) {
      await apiClient.post(`/conversations/${conversationId}/read`);
      const conversation = this.conversations.find((c) => c.id === Number(conversationId));
      if (conversation) {
        conversation.unread_count = 0;
      }
    },

    async reportConversation(conversationId, reason) {
      await apiClient.post(`/conversations/${conversationId}/report`, { reason });
    },

    subscribeToConversation(conversationId) {
      this.unsubscribeFromConversation();
      this.subscribedConversationId = conversationId;
      echo.private(`conversation.${conversationId}`).listen(".message.sent", (payload) => {
        this.messages.push(payload);
        this._touchConversation(conversationId, payload);
      });
    },

    unsubscribeFromConversation() {
      if (this.subscribedConversationId) {
        echo.leave(`conversation.${this.subscribedConversationId}`);
        this.subscribedConversationId = null;
      }
    },

    _upsertConversation(conversation) {
      const index = this.conversations.findIndex((c) => c.id === conversation.id);
      if (index !== -1) {
        this.conversations[index] = conversation;
      } else {
        this.conversations.unshift(conversation);
      }
    },

    _touchConversation(conversationId, message) {
      const conversation = this.conversations.find((c) => c.id === Number(conversationId));
      if (conversation) {
        conversation.last_message = { body: message.body, sender_id: message.sender.id, created_at: message.created_at };
      }
    },
  },
});
