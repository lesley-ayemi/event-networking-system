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

  it("fetchReports() loads reports with the given filters and stores pagination meta", async () => {
    get.mockResolvedValue({
      data: {
        data: [{ id: 1, reason: "spam" }],
        meta: { current_page: 1, last_page: 3 },
      },
    });

    const store = useAdminStore();
    await store.fetchReports({ type: "user", status: "pending", page: 1 });

    expect(get).toHaveBeenCalledWith("/admin/reports", { params: { type: "user", status: "pending", page: 1 } });
    expect(store.reports).toEqual([{ id: 1, reason: "spam" }]);
    expect(store.reportsPagination).toEqual({ current_page: 1, last_page: 3 });
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
    get.mockResolvedValue({
      data: { data: [{ id: 2, reports_count: 5 }], meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 } },
    });

    const store = useAdminStore();
    await store.fetchFlaggedAccounts();

    expect(get).toHaveBeenCalledWith("/admin/flagged-accounts", { params: {} });
    expect(store.flaggedAccounts).toEqual([{ id: 2, reports_count: 5 }]);
    expect(store.flaggedPagination).toEqual({ current_page: 1, last_page: 1, per_page: 20, total: 1 });
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

  it("fetchEventRegistrations() loads attendees for an event", async () => {
    get.mockResolvedValue({ data: { data: [{ id: 1, user: { first_name: "Ben" } }] } });

    const store = useAdminStore();
    await store.fetchEventRegistrations(9);

    expect(get).toHaveBeenCalledWith("/admin/events/9/registrations");
    expect(store.eventRegistrations).toEqual([{ id: 1, user: { first_name: "Ben" } }]);
  });

  it("removeEventRegistration() removes the registration from the list", async () => {
    del.mockResolvedValue({});

    const store = useAdminStore();
    store.eventRegistrations = [{ id: 1 }, { id: 2 }];
    await store.removeEventRegistration(9, 1);

    expect(del).toHaveBeenCalledWith("/admin/events/9/registrations/1");
    expect(store.eventRegistrations).toEqual([{ id: 2 }]);
  });

  it("fetchReportContext() loads the surrounding conversation for a reported message", async () => {
    get.mockResolvedValue({
      data: { data: [{ id: 1, body: "Hi", is_flagged: false }, { id: 2, body: "Rude", is_flagged: true }] },
    });

    const store = useAdminStore();
    await store.fetchReportContext(7);

    expect(get).toHaveBeenCalledWith("/admin/reports/7/context");
    expect(store.reportContext).toHaveLength(2);
    expect(store.reportContextReportId).toBe(7);
  });

  it("clearReportContext() resets the context state", () => {
    const store = useAdminStore();
    store.reportContext = [{ id: 1 }];
    store.reportContextReportId = 7;
    store.reportContextError = "oops";

    store.clearReportContext();

    expect(store.reportContext).toEqual([]);
    expect(store.reportContextReportId).toBeNull();
    expect(store.reportContextError).toBe("");
  });

  it("updateEvent() patches the event and returns the updated data", async () => {
    const updated = { id: 9, name: "Updated by admin" };
    patch.mockResolvedValue({ data: { data: updated } });

    const store = useAdminStore();
    const result = await store.updateEvent(9, { name: "Updated by admin" });

    expect(patch).toHaveBeenCalledWith("/admin/events/9", { name: "Updated by admin" });
    expect(result).toEqual(updated);
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

  it("fetchAuditLogs() loads the log and stores pagination meta", async () => {
    get.mockResolvedValue({
      data: {
        data: [{ id: 1, action: "user.suspended" }],
        meta: { current_page: 1, last_page: 2 },
      },
    });

    const store = useAdminStore();
    await store.fetchAuditLogs();

    expect(get).toHaveBeenCalledWith("/admin/audit-logs", { params: {} });
    expect(store.auditLogs).toEqual([{ id: 1, action: "user.suspended" }]);
    expect(store.auditLogsPagination).toEqual({ current_page: 1, last_page: 2 });
  });

  it("fetchAuditLogs() passes the requested page through", async () => {
    get.mockResolvedValue({ data: { data: [], meta: { current_page: 2, last_page: 2 } } });

    const store = useAdminStore();
    await store.fetchAuditLogs({ page: 2 });

    expect(get).toHaveBeenCalledWith("/admin/audit-logs", { params: { page: 2 } });
  });

  it("fetchAdmins() loads admin accounts", async () => {
    get.mockResolvedValue({
      data: { data: [{ id: 1, is_admin: true }], meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 } },
    });

    const store = useAdminStore();
    await store.fetchAdmins();

    expect(get).toHaveBeenCalledWith("/admin/admins", { params: {} });
    expect(store.admins).toEqual([{ id: 1, is_admin: true }]);
    expect(store.adminsPagination).toEqual({ current_page: 1, last_page: 1, per_page: 20, total: 1 });
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

  it("fetchUsers() loads users with the given filters and stores pagination meta", async () => {
    get.mockResolvedValue({
      data: {
        data: [{ id: 1, first_name: "Ben" }],
        meta: { current_page: 1, last_page: 2 },
      },
    });

    const store = useAdminStore();
    await store.fetchUsers({ search: "Ben", page: 1 });

    expect(get).toHaveBeenCalledWith("/admin/users", { params: { search: "Ben", page: 1 } });
    expect(store.users).toEqual([{ id: 1, first_name: "Ben" }]);
    expect(store.usersPagination).toEqual({ current_page: 1, last_page: 2 });
  });

  it("fetchUser() loads a single user", async () => {
    get.mockResolvedValue({ data: { id: 1, first_name: "Ben" } });

    const store = useAdminStore();
    await store.fetchUser(1);

    expect(get).toHaveBeenCalledWith("/admin/users/1");
    expect(store.currentUser).toEqual({ id: 1, first_name: "Ben" });
  });

  it("updateUser() updates the currentUser and the matching row in the users list", async () => {
    const updated = { id: 1, first_name: "Benjamin" };
    patch.mockResolvedValue({ data: updated });

    const store = useAdminStore();
    store.currentUser = { id: 1, first_name: "Ben" };
    store.users = [{ id: 1, first_name: "Ben" }, { id: 2, first_name: "Ava" }];

    const result = await store.updateUser(1, { first_name: "Benjamin" });

    expect(patch).toHaveBeenCalledWith("/admin/users/1", { first_name: "Benjamin" });
    expect(store.currentUser).toEqual(updated);
    expect(store.users[0]).toEqual(updated);
    expect(result).toEqual(updated);
  });

  it("deleteUser() removes the user from the list and clears currentUser if it matches", async () => {
    del.mockResolvedValue({});

    const store = useAdminStore();
    store.users = [{ id: 1 }, { id: 2 }];
    store.currentUser = { id: 1 };

    await store.deleteUser(1);

    expect(del).toHaveBeenCalledWith("/admin/users/1");
    expect(store.users).toEqual([{ id: 2 }]);
    expect(store.currentUser).toBeNull();
  });

  it("suspendUser() and unsuspendUser() sync the updated user into currentUser and the users list", async () => {
    const suspended = { id: 1, is_suspended: true };
    post.mockResolvedValue({ data: suspended });

    const store = useAdminStore();
    store.currentUser = { id: 1, is_suspended: false };
    store.users = [{ id: 1, is_suspended: false }];

    await store.suspendUser(1);

    expect(store.currentUser).toEqual(suspended);
    expect(store.users[0]).toEqual(suspended);

    const unsuspended = { id: 1, is_suspended: false };
    post.mockResolvedValue({ data: unsuspended });
    await store.unsuspendUser(1);

    expect(store.currentUser).toEqual(unsuspended);
  });
});
