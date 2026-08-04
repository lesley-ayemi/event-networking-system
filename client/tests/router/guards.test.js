import { describe, it, expect } from "vitest";
import { resolveNavigation } from "../../src/router/guards.js";

describe("resolveNavigation", () => {
  it("allows navigation to a route that does not require auth", () => {
    const to = { meta: { requiresAuth: false }, path: "/" };
    const result = resolveNavigation(to, { isAuthenticated: false });
    expect(result).toBe(true);
  });

  it("allows navigation to a protected route when authenticated", () => {
    const to = { meta: { requiresAuth: true }, path: "/dashboard" };
    const result = resolveNavigation(to, { isAuthenticated: true });
    expect(result).toBe(true);
  });

  it("redirects to /login when navigating to a protected route while unauthenticated", () => {
    const to = { meta: { requiresAuth: true }, path: "/dashboard" };
    const result = resolveNavigation(to, { isAuthenticated: false });
    expect(result).toEqual({ path: "/login", query: { redirect: "/dashboard" } });
  });

  it("allows navigation to an admin route when authenticated and an admin", () => {
    const to = { meta: { requiresAuth: true, requiresAdmin: true }, path: "/admin/reports" };
    const result = resolveNavigation(to, { isAuthenticated: true, isAdmin: true });
    expect(result).toBe(true);
  });

  it("redirects to /dashboard when navigating to an admin route as a non-admin", () => {
    const to = { meta: { requiresAuth: true, requiresAdmin: true }, path: "/admin/reports" };
    const result = resolveNavigation(to, { isAuthenticated: true, isAdmin: false });
    expect(result).toEqual({ path: "/dashboard" });
  });
});
