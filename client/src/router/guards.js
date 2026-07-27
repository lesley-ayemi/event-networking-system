export function resolveNavigation(to, authState) {
  const requiresAuth = to.meta?.requiresAuth === true;

  if (!requiresAuth) {
    return true;
  }

  if (authState.isAuthenticated) {
    return true;
  }

  return { path: "/login", query: { redirect: to.path } };
}
