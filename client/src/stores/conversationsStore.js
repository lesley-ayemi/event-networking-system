import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";
import { getApiError } from "../services/apiError.js";
import { echo } from "../services/echo.js";

// DefaultLayout re-mounts on every page navigation (each page wraps itself
// in <DefaultLayout> rather than sharing one persistent instance), and it
// calls fetchConversations() on mount to keep the unread badge current.
// Without this cache, clicking through several pages in a row refetches the
// whole conversation list every single time, which is exactly the kind of
// burst that trips the API's per-minute rate limit.
const STALE_AFTER_MS = 30000;

export const useConversationsStore = defineStore("conversations", {
  state: () => ({
    conversations: [],
    currentConversation: null,
    messages: [],
    isLoading: false,
    error: "",
    subscribedConversationId: null,
    lastFetchedAt: 0,
    _fetchPromise: null,
  }),

  getters: {
    unreadTotal: (state) => state.conversations.reduce((total, conversation) => total + conversation.unread_count, 0),
  },

  actions: {
    // DefaultLayout's onMounted and the current page's own onMounted (e.g.
    // MessagesPage, DashboardPage) both call this in the same render tick —
    // before either request has resolved and updated lastFetchedAt. Without
    // tracking the in-flight promise, both slip past the freshness check
    // above and fire duplicate requests.
    fetchConversations() {
      if (this.lastFetchedAt && Date.now() - this.lastFetchedAt < STALE_AFTER_MS) {
        return Promise.resolve();
      }
      if (this._fetchPromise) {
        return this._fetchPromise;
      }
      this.isLoading = true;
      this.error = "";
      this._fetchPromise = apiClient
        .get("/conversations")
        .then((response) => {
          this.conversations = response.data.data;
          this.lastFetchedAt = Date.now();
        })
        .catch((error) => {
          this.error = getApiError(error, "We couldn't load your conversations right now. Please try again.").message;
        })
        .finally(() => {
          this.isLoading = false;
          this._fetchPromise = null;
        });
      return this._fetchPromise;
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
        this.error = getApiError(error, "We couldn't load this conversation.").message;
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
