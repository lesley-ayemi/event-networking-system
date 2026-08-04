import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const post = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: {
    post: (...args) => post(...args),
  },
}));

const { useReportStore } = await import("../../src/stores/reportStore.js");

describe("reportStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    post.mockReset();
  });

  it("submitReport() posts the report and returns the created record", async () => {
    const created = { id: 1, reportable_type: "user", reportable_id: 6, reason: "spam", status: "pending" };
    post.mockResolvedValue({ data: created });

    const store = useReportStore();
    const result = await store.submitReport({
      reportableType: "user",
      reportableId: 6,
      reason: "spam",
      details: "Keeps sending links.",
    });

    expect(post).toHaveBeenCalledWith("/reports", {
      reportable_type: "user",
      reportable_id: 6,
      reason: "spam",
      details: "Keeps sending links.",
    });
    expect(result).toEqual(created);
  });

  it("submitReport() sends null details when none are given", async () => {
    post.mockResolvedValue({ data: {} });

    const store = useReportStore();
    await store.submitReport({ reportableType: "event", reportableId: 3, reason: "false_event_information" });

    expect(post).toHaveBeenCalledWith("/reports", {
      reportable_type: "event",
      reportable_id: 3,
      reason: "false_event_information",
      details: null,
    });
  });
});
