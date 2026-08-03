import { describe, it, expect, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useNotificationStore } from "../../src/stores/notificationStore.js";
import { useConversationsStore } from "../../src/stores/conversationsStore.js";
import { useFriendsStore } from "../../src/stores/friendsStore.js";

describe("notificationStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("reports zero counts and no items when there is nothing to surface", () => {
    const store = useNotificationStore();

    expect(store.totalCount).toBe(0);
    expect(store.items).toEqual([]);
  });

  it("aggregates unread messages and pending friend requests from other stores", () => {
    useConversationsStore().conversations = [
      { id: 1, unread_count: 2 },
      { id: 2, unread_count: 1 },
    ];
    useFriendsStore().incomingRequests = [{ id: 10 }];

    const store = useNotificationStore();

    expect(store.unreadMessageCount).toBe(3);
    expect(store.pendingFriendRequestCount).toBe(1);
    expect(store.totalCount).toBe(4);
    expect(store.items).toEqual([
      { id: "messages", label: "3 unread messages", to: "/messages" },
      { id: "friend-requests", label: "1 pending friend request", to: "/friends" },
    ]);
  });

  it("uses singular wording for a single unread message", () => {
    useConversationsStore().conversations = [{ id: 1, unread_count: 1 }];

    const store = useNotificationStore();

    expect(store.items).toContainEqual({ id: "messages", label: "1 unread message", to: "/messages" });
  });
});
