import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const get = vi.fn();
const post = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    get: (...args) => get(...args),
    post: (...args) => post(...args),
  },
}));

const listen = vi.fn();
const privateChannel = vi.fn(() => ({ listen }));
const leave = vi.fn();
vi.mock("../../src/services/echo.js", () => ({
  echo: {
    private: (...args) => privateChannel(...args),
    leave: (...args) => leave(...args),
    socketId: () => "socket-123",
  },
}));

const { useConversationsStore } = await import("../../src/stores/conversationsStore.js");

describe("conversationsStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
    listen.mockReset();
    privateChannel.mockClear();
    leave.mockReset();
  });

  it("fetchConversations() stores the list", async () => {
    const conversations = [{ id: 1, other_user: { id: 2 }, unread_count: 2 }];
    get.mockResolvedValue({ data: { data: conversations } });

    const store = useConversationsStore();
    await store.fetchConversations();

    expect(get).toHaveBeenCalledWith("/conversations");
    expect(store.conversations).toEqual(conversations);
  });

  it("fetchConversations() skips a second call within the cache window", async () => {
    get.mockResolvedValue({ data: { data: [] } });
    const store = useConversationsStore();

    await store.fetchConversations();
    await store.fetchConversations();

    expect(get).toHaveBeenCalledTimes(1);
  });

  it("fetchConversations() dedupes concurrent calls made before the first resolves", async () => {
    let resolveGet;
    get.mockReturnValue(new Promise((resolve) => { resolveGet = resolve; }));
    const store = useConversationsStore();

    const first = store.fetchConversations();
    const second = store.fetchConversations();
    resolveGet({ data: { data: [] } });
    await Promise.all([first, second]);

    expect(get).toHaveBeenCalledTimes(1);
  });

  it("fetchConversations() refetches once the cache window has passed", async () => {
    vi.useFakeTimers();
    get.mockResolvedValue({ data: { data: [] } });
    const store = useConversationsStore();

    await store.fetchConversations();
    await vi.advanceTimersByTimeAsync(30001);
    await store.fetchConversations();

    expect(get).toHaveBeenCalledTimes(2);
    vi.useRealTimers();
  });

  it("unreadTotal sums unread_count across conversations", () => {
    const store = useConversationsStore();
    store.conversations = [{ id: 1, unread_count: 2 }, { id: 2, unread_count: 3 }];

    expect(store.unreadTotal).toBe(5);
  });

  it("startConversation() posts the recipient and adds the conversation to the list", async () => {
    const conversation = { id: 5, other_user: { id: 2 }, unread_count: 0 };
    post.mockResolvedValue({ data: conversation });

    const store = useConversationsStore();
    await store.startConversation(2);

    expect(post).toHaveBeenCalledWith("/conversations", { recipient_id: 2 });
    expect(store.conversations).toEqual([conversation]);
  });

  it("fetchMessages() stores the messages", async () => {
    const messages = [{ id: 1, body: "Hi", sender: { id: 2 } }];
    get.mockResolvedValue({ data: { data: messages } });

    const store = useConversationsStore();
    await store.fetchMessages(5);

    expect(get).toHaveBeenCalledWith("/conversations/5/messages");
    expect(store.messages).toEqual(messages);
  });

  it("sendMessage() appends the new message and updates the conversation preview", async () => {
    const message = { id: 2, body: "Hello!", sender: { id: 1 }, created_at: "2026-08-03T00:00:00Z" };
    post.mockResolvedValue({ data: message });

    const store = useConversationsStore();
    store.conversations = [{ id: 5, last_message: null }];

    await store.sendMessage(5, "Hello!");

    expect(post).toHaveBeenCalledWith("/conversations/5/messages", { body: "Hello!" });
    expect(store.messages).toEqual([message]);
    expect(store.conversations[0].last_message).toEqual({ body: "Hello!", sender_id: 1, created_at: "2026-08-03T00:00:00Z" });
  });

  it("markRead() zeroes the unread count for that conversation", async () => {
    post.mockResolvedValue({ data: { message: "Marked as read." } });

    const store = useConversationsStore();
    store.conversations = [{ id: 5, unread_count: 3 }];

    await store.markRead(5);

    expect(post).toHaveBeenCalledWith("/conversations/5/read");
    expect(store.conversations[0].unread_count).toBe(0);
  });

  it("subscribeToConversation() listens on the conversation's private channel", () => {
    const store = useConversationsStore();

    store.subscribeToConversation(5);

    expect(privateChannel).toHaveBeenCalledWith("conversation.5");
    expect(listen).toHaveBeenCalledWith(".message.sent", expect.any(Function));
  });

  it("subscribeToConversation() leaves any previously subscribed channel first", () => {
    const store = useConversationsStore();

    store.subscribeToConversation(5);
    store.subscribeToConversation(6);

    expect(leave).toHaveBeenCalledWith("conversation.5");
    expect(privateChannel).toHaveBeenCalledWith("conversation.6");
  });

  it("a live message pushes into messages and updates the conversation preview", () => {
    const store = useConversationsStore();
    store.conversations = [{ id: 5, last_message: null }];

    store.subscribeToConversation(5);
    const liveHandler = listen.mock.calls[0][1];
    liveHandler({ id: 9, body: "Live!", sender: { id: 2 }, created_at: "2026-08-03T00:01:00Z" });

    expect(store.messages).toEqual([{ id: 9, body: "Live!", sender: { id: 2 }, created_at: "2026-08-03T00:01:00Z" }]);
    expect(store.conversations[0].last_message).toEqual({ body: "Live!", sender_id: 2, created_at: "2026-08-03T00:01:00Z" });
  });
});
