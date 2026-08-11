import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const get = vi.fn();
const post = vi.fn();
const patch = vi.fn();
const del = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    get: (...args) => get(...args),
    post: (...args) => post(...args),
    patch: (...args) => patch(...args),
    delete: (...args) => del(...args),
  },
}));

const { useFriendsStore } = await import("../../src/stores/friendsStore.js");

describe("friendsStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
    patch.mockReset();
    del.mockReset();
  });

  it("fetchAll() loads friends, incoming, and outgoing requests together", async () => {
    get.mockImplementation((url) => {
      if (url === "/friends") return Promise.resolve({ data: { data: [{ id: 1, first_name: "Sam" }] } });
      if (url === "/friends/requests/incoming") return Promise.resolve({ data: { data: [{ id: 10 }] } });
      if (url === "/friends/requests/outgoing") return Promise.resolve({ data: { data: [{ id: 20 }] } });
      return Promise.reject(new Error(`unexpected url ${url}`));
    });

    const store = useFriendsStore();
    await store.fetchAll();

    expect(store.friends).toEqual([{ id: 1, first_name: "Sam" }]);
    expect(store.incomingRequests).toEqual([{ id: 10 }]);
    expect(store.outgoingRequests).toEqual([{ id: 20 }]);
  });

  it("fetchAll() skips a second call within the cache window", async () => {
    get.mockResolvedValue({ data: { data: [] } });
    const store = useFriendsStore();

    await store.fetchAll();
    await store.fetchAll();

    expect(get).toHaveBeenCalledTimes(3);
  });

  it("fetchAll() dedupes concurrent calls made before the first resolves", async () => {
    const resolvers = [];
    get.mockImplementation(() => new Promise((resolve) => resolvers.push(resolve)));
    const store = useFriendsStore();

    const first = store.fetchAll();
    const second = store.fetchAll();
    resolvers.forEach((resolve) => resolve({ data: { data: [] } }));
    await Promise.all([first, second]);

    expect(get).toHaveBeenCalledTimes(3);
  });

  it("fetchAll() refetches once the cache window has passed", async () => {
    vi.useFakeTimers();
    get.mockResolvedValue({ data: { data: [] } });
    const store = useFriendsStore();

    await store.fetchAll();
    await vi.advanceTimersByTimeAsync(30001);
    await store.fetchAll();

    expect(get).toHaveBeenCalledTimes(6);
    vi.useRealTimers();
  });

  it("fetchAll() records an error on failure", async () => {
    get.mockRejectedValue(new Error("network error"));

    const store = useFriendsStore();
    await store.fetchAll();

    expect(store.error).toBe("We couldn't load your friends right now. Please try again.");
  });

  it("sendFriendRequest() posts the recipient and adds it to outgoing", async () => {
    const newRequest = { id: 5, recipient: { id: 2, first_name: "Sam" }, status: "pending" };
    post.mockResolvedValue({ data: newRequest });

    const store = useFriendsStore();
    await store.sendFriendRequest(2);

    expect(post).toHaveBeenCalledWith("/friends/requests", { recipient_id: 2 });
    expect(store.outgoingRequests).toEqual([newRequest]);
  });

  it("acceptRequest() moves the sender from incoming into friends", async () => {
    const sender = { id: 2, first_name: "Sam" };
    patch.mockResolvedValue({ data: { id: 5, sender, status: "accepted" } });

    const store = useFriendsStore();
    store.incomingRequests = [{ id: 5, sender }];

    await store.acceptRequest(5);

    expect(patch).toHaveBeenCalledWith("/friends/requests/5/accept");
    expect(store.incomingRequests).toEqual([]);
    expect(store.friends).toEqual([sender]);
  });

  it("declineRequest() removes it from incoming", async () => {
    patch.mockResolvedValue({ data: { message: "Request declined." } });

    const store = useFriendsStore();
    store.incomingRequests = [{ id: 5, sender: { id: 2 } }];

    await store.declineRequest(5);

    expect(patch).toHaveBeenCalledWith("/friends/requests/5/decline");
    expect(store.incomingRequests).toEqual([]);
  });

  it("removeFriend() deletes and drops the friend locally", async () => {
    del.mockResolvedValue({ data: { message: "Friend removed." } });

    const store = useFriendsStore();
    store.friends = [{ id: 2, first_name: "Sam" }];

    await store.removeFriend(2);

    expect(del).toHaveBeenCalledWith("/friends/2");
    expect(store.friends).toEqual([]);
  });

  it("blockUser() removes the user from friends, incoming, and outgoing", async () => {
    post.mockResolvedValue({ data: { message: "User blocked." } });

    const store = useFriendsStore();
    store.friends = [{ id: 2, first_name: "Sam" }];
    store.incomingRequests = [{ id: 9, sender: { id: 2 } }];
    store.outgoingRequests = [{ id: 11, recipient: { id: 2 } }];

    await store.blockUser(2);

    expect(post).toHaveBeenCalledWith("/blocks/2");
    expect(store.friends).toEqual([]);
    expect(store.incomingRequests).toEqual([]);
    expect(store.outgoingRequests).toEqual([]);
  });
});
