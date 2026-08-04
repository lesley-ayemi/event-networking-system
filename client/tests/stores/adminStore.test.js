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

const { useAdminStore } = await import("../../src/stores/adminStore.js");

describe("adminStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    get.mockReset();
    post.mockReset();
    patch.mockReset();
    del.mockReset();
  });

  it("fetchReports() loads reports with the given filters", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 1, reason: "spam" }] } });

    const store = useAdminStore();
    await store.fetchReports({ type: "user", status: "pending" });

    expect(get).toHaveBeenCalledWith("/admin/reports", { params: { type: "user", status: "pending" } });
    expect(store.reports).toEqual([{ id: 1, reason: "spam" }]);
  });

  it("updateReportStatus() updates the report in place", async () => {
    const updated = { id: 1, reason: "spam", status: "dismissed" };
    patch.mockResolvedValue({ data: updated });

    const store = useAdminStore();
    store.reports = [{ id: 1, reason: "spam", status: "pending" }];
    await store.updateReportStatus(1, "dismissed");

    expect(patch).toHaveBeenCalledWith("/admin/reports/1", { status: "dismissed" });
    expect(store.reports[0]).toEqual(updated);
  });

  it("fetchFlaggedAccounts() loads flagged users", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 2, reports_count: 5 }] } });

    const store = useAdminStore();
    await store.fetchFlaggedAccounts();

    expect(get).toHaveBeenCalledWith("/admin/flagged-accounts");
    expect(store.flaggedAccounts).toEqual([{ id: 2, reports_count: 5 }]);
  });

  it("suspendUser() removes the user from the flagged list", async () => {
    post.mockResolvedValue({ data: {} });

    const store = useAdminStore();
    store.flaggedAccounts = [{ id: 2 }, { id: 3 }];
    await store.suspendUser(2);

    expect(post).toHaveBeenCalledWith("/admin/users/2/suspend");
    expect(store.flaggedAccounts).toEqual([{ id: 3 }]);
  });

  it("removeEvent() deletes the event", async () => {
    del.mockResolvedValue({});

    const store = useAdminStore();
    await store.removeEvent(9);

    expect(del).toHaveBeenCalledWith("/admin/events/9");
  });

  it("approveOrganiser() and rejectOrganiser() remove the user from the pending list", async () => {
    post.mockResolvedValue({ data: {} });

    const store = useAdminStore();
    store.organiserRequests = [{ id: 4 }, { id: 5 }];
    await store.approveOrganiser(4);
    expect(post).toHaveBeenCalledWith("/admin/organiser-requests/4/approve");
    expect(store.organiserRequests).toEqual([{ id: 5 }]);

    await store.rejectOrganiser(5);
    expect(post).toHaveBeenCalledWith("/admin/organiser-requests/5/reject");
    expect(store.organiserRequests).toEqual([]);
  });

  it("fetchAuditLogs() loads the log", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 1, action: "user.suspended" }] } });

    const store = useAdminStore();
    await store.fetchAuditLogs();

    expect(get).toHaveBeenCalledWith("/admin/audit-logs");
    expect(store.auditLogs).toEqual([{ id: 1, action: "user.suspended" }]);
  });

  it("fetchAdmins() loads admin accounts", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 1, is_admin: true }] } });

    const store = useAdminStore();
    await store.fetchAdmins();

    expect(get).toHaveBeenCalledWith("/admin/admins");
    expect(store.admins).toEqual([{ id: 1, is_admin: true }]);
  });

  it("createAdmin() adds the new admin to the list", async () => {
    const created = { id: 10, email: "new-admin@example.com", is_admin: true };
    post.mockResolvedValue({ data: created });

    const store = useAdminStore();
    const result = await store.createAdmin({ first_name: "New", last_name: "Admin", email: "new-admin@example.com" });

    expect(post).toHaveBeenCalledWith("/admin/admins", {
      first_name: "New", last_name: "Admin", email: "new-admin@example.com",
    });
    expect(store.admins).toEqual([created]);
    expect(result).toEqual(created);
  });

  it("demoteAdmin() removes the admin from the list", async () => {
    del.mockResolvedValue({});

    const store = useAdminStore();
    store.admins = [{ id: 1 }, { id: 2 }];
    await store.demoteAdmin(1);

    expect(del).toHaveBeenCalledWith("/admin/admins/1");
    expect(store.admins).toEqual([{ id: 2 }]);
  });
});
