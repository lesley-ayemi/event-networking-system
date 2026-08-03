import { defineStore } from "pinia";
import { useConversationsStore } from "./conversationsStore.js";
import { useFriendsStore } from "./friendsStore.js";

// Firebase apps usually back this with a Firestore "notifications" collection
// populated by Cloud Functions. There's no equivalent table here yet, so this
// store aggregates the notification-worthy signals the app already fetches
// (unread messages, pending friend requests) into one place instead.
export const useNotificationStore = defineStore("notifications", {
  getters: {
    unreadMessageCount: () => useConversationsStore().unreadTotal,
    pendingFriendRequestCount: () => useFriendsStore().incomingRequests.length,

    totalCount() {
      return this.unreadMessageCount + this.pendingFriendRequestCount;
    },

    items() {
      const items = [];

      if (this.unreadMessageCount > 0) {
        items.push({
          id: "messages",
          label: `${this.unreadMessageCount} unread message${this.unreadMessageCount === 1 ? "" : "s"}`,
          to: "/messages",
        });
      }

      if (this.pendingFriendRequestCount > 0) {
        items.push({
          id: "friend-requests",
          label: `${this.pendingFriendRequestCount} pending friend request${this.pendingFriendRequestCount === 1 ? "" : "s"}`,
          to: "/friends",
        });
      }

      return items;
    },
  },
});
