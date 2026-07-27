import { createRouter, createWebHistory } from "vue-router";
import { resolveNavigation } from "./guards.js";
import { useAuthStore } from "../stores/authStore.js";

import LandingPage from "../pages/LandingPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";
import LoginPage from "../pages/LoginPage.vue";
import OnboardingPage from "../pages/OnboardingPage.vue";
import CompatibilityQuizPage from "../pages/CompatibilityQuizPage.vue";
import DashboardPage from "../pages/DashboardPage.vue";
import EventsPage from "../pages/EventsPage.vue";
import EventDetailsPage from "../pages/EventDetailsPage.vue";
import SavedEventsPage from "../pages/SavedEventsPage.vue";
import MatchesPage from "../pages/MatchesPage.vue";
import FriendsPage from "../pages/FriendsPage.vue";
import MessagesPage from "../pages/MessagesPage.vue";
import ChatPage from "../pages/ChatPage.vue";
import ProfilePage from "../pages/ProfilePage.vue";

const routes = [
  { path: "/", component: LandingPage, meta: { requiresAuth: false } },
  { path: "/register", component: RegisterPage, meta: { requiresAuth: false } },
  { path: "/login", component: LoginPage, meta: { requiresAuth: false } },
  { path: "/onboarding", component: OnboardingPage, meta: { requiresAuth: true } },
  { path: "/quiz", component: CompatibilityQuizPage, meta: { requiresAuth: true } },
  { path: "/dashboard", component: DashboardPage, meta: { requiresAuth: true } },
  { path: "/events", component: EventsPage, meta: { requiresAuth: true } },
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
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const authStore = useAuthStore();
  return resolveNavigation(to, { isAuthenticated: authStore.isAuthenticated });
});
