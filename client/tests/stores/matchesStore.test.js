import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { get: (...args) => get(...args) },
}));

const { useMatchesStore } = await import("../../src/stores/matchesStore.js");

describe("matchesStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
  });

  it("fetchMatches() stores the matches", async () => {
    const matches = [
      {
        event: { id: 1, name: "Founders Mixer", starts_at: "2026-09-01T18:00:00Z" },
        user: { id: 2, first_name: "Sam", last_name: "Rivera" },
        score: 91,
        reasons: ["Prefer one-to-one conversations"],
      },
    ];
    get.mockResolvedValue({ data: { data: matches } });

    const store = useMatchesStore();
    await store.fetchMatches();

    expect(get).toHaveBeenCalledWith("/matches");
    expect(store.matches).toEqual(matches);
  });

  it("fetchMatches() records an error on failure", async () => {
    get.mockRejectedValue(new Error("network error"));

    const store = useMatchesStore();
    await store.fetchMatches();

    expect(store.error).toBe("We couldn't load your matches right now. Please try again.");
    expect(store.matches).toEqual([]);
  });
});
