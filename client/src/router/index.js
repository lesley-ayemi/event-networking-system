import { createRouter, createWebHistory } from "vue-router";
import { resolveNavigation } from "./guards.js";
import { useAuthStore } from "../stores/authStore.js";
import { useUserStore } from "../stores/userStore.js";

import LandingPage from "../pages/LandingPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";
import LoginPage from "../pages/LoginPage.vue";
import ForgotPasswordPage from "../pages/ForgotPasswordPage.vue";
import ResetPasswordPage from "../pages/ResetPasswordPage.vue";
import OnboardingPage from "../pages/OnboardingPage.vue";
import CompatibilityQuizPage from "../pages/CompatibilityQuizPage.vue";
import DashboardPage from "../pages/DashboardPage.vue";
import EventsPage from "../pages/EventsPage.vue";
import EventDetailsPage from "../pages/EventDetailsPage.vue";
import CreateEventPage from "../pages/CreateEventPage.vue";
import EditEventPage from "../pages/EditEventPage.vue";
import MyEventsPage from "../pages/MyEventsPage.vue";
import SavedEventsPage from "../pages/SavedEventsPage.vue";
import MatchesPage from "../pages/MatchesPage.vue";
import FriendsPage from "../pages/FriendsPage.vue";
import MessagesPage from "../pages/MessagesPage.vue";
import ChatPage from "../pages/ChatPage.vue";
import ProfilePage from "../pages/ProfilePage.vue";
import UserProfilePage from "../pages/UserProfilePage.vue";
import AdminUsersPage from "../pages/admin/AdminUsersPage.vue";
import AdminUserDetailPage from "../pages/admin/AdminUserDetailPage.vue";
import AdminReportsPage from "../pages/admin/AdminReportsPage.vue";
import AdminFlaggedAccountsPage from "../pages/admin/AdminFlaggedAccountsPage.vue";
import AdminOrganiserRequestsPage from "../pages/admin/AdminOrganiserRequestsPage.vue";
import AdminEventsPage from "../pages/admin/AdminEventsPage.vue";
import AdminAuditLogPage from "../pages/admin/AdminAuditLogPage.vue";
import AdminAdminsPage from "../pages/admin/AdminAdminsPage.vue";

const routes = [
  { path: "/", component: LandingPage, meta: { requiresAuth: false } },
  { path: "/register", component: RegisterPage, meta: { requiresAuth: false } },
  { path: "/login", component: LoginPage, meta: { requiresAuth: false } },
  { path: "/forgot-password", component: ForgotPasswordPage, meta: { requiresAuth: false } },
  { path: "/reset-password", component: ResetPasswordPage, meta: { requiresAuth: false } },
  { path: "/onboarding", component: OnboardingPage, meta: { requiresAuth: true } },
  { path: "/quiz", component: CompatibilityQuizPage, meta: { requiresAuth: true } },
  { path: "/dashboard", component: DashboardPage, meta: { requiresAuth: true } },
  { path: "/events", component: EventsPage, meta: { requiresAuth: true } },
  { path: "/events/new", component: CreateEventPage, meta: { requiresAuth: true, requiresOrganiser: true } },
  { path: "/my-events", component: MyEventsPage, meta: { requiresAuth: true, requiresOrganiser: true } },
  { path: "/events/:id/edit", component: EditEventPage, meta: { requiresAuth: true, requiresOrganiser: true } },
  { path: "/events/:id", component: EventDetailsPage, meta: { requiresAuth: true } },
  { path: "/saved-events", component: SavedEventsPage, meta: { requiresAuth: true } },
  { path: "/matches", component: MatchesPage, meta: { requiresAuth: true } },
  { path: "/friends", component: FriendsPage, meta: { requiresAuth: true } },
  { path: "/messages", component: MessagesPage, meta: { requiresAuth: true } },
  {
    path: "/messages/:conversationId",
    component: ChatPage,
    meta: { requiresAuth: true },
  },
  { path: "/profile", component: ProfilePage, meta: { requiresAuth: true } },
  { path: "/users/:id", component: UserProfilePage, meta: { requiresAuth: true } },
  { path: "/admin/users", component: AdminUsersPage, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/admin/users/:id", component: AdminUserDetailPage, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/admin/reports", component: AdminReportsPage, meta: { requiresAuth: true, requiresAdmin: true } },
  {
    path: "/admin/flagged-accounts",
    component: AdminFlaggedAccountsPage,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: "/admin/organiser-requests",
    component: AdminOrganiserRequestsPage,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  { path: "/admin/events", component: AdminEventsPage, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/admin/audit-log", component: AdminAuditLogPage, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/admin/admins", component: AdminAdminsPage, meta: { requiresAuth: true, requiresAdmin: true } },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const authStore = useAuthStore();
  const userStore = useUserStore();
  return resolveNavigation(to, {
    isAuthenticated: authStore.isAuthenticated,
    isAdmin: Boolean(userStore.user?.is_admin),
    isApprovedOrganiser: userStore.user?.organiser_status === "approved",
  });
});
