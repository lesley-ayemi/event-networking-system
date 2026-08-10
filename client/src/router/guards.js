export function resolveNavigation(to, authState) {
  const requiresAuth = to.meta?.requiresAuth === true;

  if (!requiresAuth) {
    if (to.meta?.guestOnly === true && authState.isAuthenticated) {
      return { path: authState.isAdmin ? "/admin" : "/dashboard" };
    }
    return true;
  }

  if (!authState.isAuthenticated) {
    return { path: "/login", query: { redirect: to.path } };
  }

  if (to.meta?.requiresAdmin === true && !authState.isAdmin) {
    return { path: "/dashboard" };
  }

  if (to.meta?.requiresOrganiser === true && !authState.isApprovedOrganiser) {
    return { path: "/profile" };
  }

  return true;
}
