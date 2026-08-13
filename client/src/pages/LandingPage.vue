<template>
  <div class="bg-white">
    <header class="max-w-6xl mx-auto px-6 flex items-center justify-between h-16">
      <RouterLink to="/" class="text-lg font-bold text-gray-900 tracking-tight">
        Event<span class="text-indigo-600">Networking</span>
      </RouterLink>
      <nav class="flex items-center gap-4">
        <template v-if="userStore.user">
          <button type="button" class="text-sm font-medium text-gray-600 hover:text-gray-900" @click="handleLogout">
            Log out
          </button>
          <RouterLink
            :to="dashboardLink"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition"
          >
            {{ isAdmin ? "Admin dashboard" : "Back to dashboard" }}
          </RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900">Log in</RouterLink>
          <RouterLink
            to="/register"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition"
          >
            Get started
          </RouterLink>
        </template>
      </nav>
    </header>

    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div
        class="absolute inset-0 -z-10 bg-gradient-to-b from-indigo-50 via-white to-white"
        aria-hidden="true"
      />
      <div class="max-w-6xl mx-auto px-6 pt-16 pb-20 text-center">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">
          Networking, without the small talk
        </span>
        <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight max-w-3xl mx-auto">
          Find your people at events,
          <span class="text-indigo-600">at your own pace.</span>
        </h1>
        <p class="mt-5 text-lg text-gray-600 max-w-xl mx-auto">
          Pick an event. Tell us how you like to talk to people. We'll show you a
          few others going who match — and you can message them days before you
          walk in, so the first conversation isn't a cold one.
        </p>
        <div class="mt-8 flex items-center justify-center gap-4">
          <template v-if="userStore.user">
            <RouterLink
              :to="dashboardLink"
              class="inline-flex items-center px-6 py-3 bg-indigo-600 rounded-xl font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md transition"
            >
              {{ isAdmin ? "Go to admin dashboard" : "Go to dashboard" }}
            </RouterLink>
            <button
              type="button"
              class="inline-flex items-center px-6 py-3 bg-white border border-gray-200 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition"
              @click="handleLogout"
            >
              Log out
            </button>
          </template>
          <template v-else>
            <RouterLink
              to="/register"
              class="inline-flex items-center px-6 py-3 bg-indigo-600 rounded-xl font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md transition"
            >
              Get started — it's free
            </RouterLink>
            <RouterLink
              to="/login"
              class="inline-flex items-center px-6 py-3 bg-white border border-gray-200 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition"
            >
              Log in
            </RouterLink>
          </template>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="max-w-6xl mx-auto px-6 py-16">
      <h2 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide text-center">How it works</h2>
      <p class="mt-2 text-2xl font-bold text-gray-900 text-center">From booking a ticket to knowing someone there</p>

      <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-8">
        <div v-for="(step, index) in steps" :key="step.title" class="text-center">
          <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white font-bold">
            {{ index + 1 }}
          </div>
          <h3 class="mt-4 font-semibold text-gray-900">{{ step.title }}</h3>
          <p class="mt-2 text-sm text-gray-600">{{ step.description }}</p>
        </div>
      </div>
    </section>

    <!-- Feature grid -->
    <section class="bg-gray-50 py-16">
      <div class="max-w-6xl mx-auto px-6">
        <p class="text-2xl font-bold text-gray-900 text-center">Built for people who don't want to work the room</p>
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="feature in features" :key="feature.title" class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg" :class="feature.badgeClass">
              {{ feature.emoji }}
            </div>
            <h3 class="mt-4 font-semibold text-gray-900">{{ feature.title }}</h3>
            <p class="mt-1.5 text-sm text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Safety callout -->
    <section class="max-w-6xl mx-auto px-6 py-16">
      <div class="bg-indigo-600 rounded-2xl px-8 py-10 sm:px-12 sm:py-14 text-center">
        <p class="text-sm font-semibold text-indigo-200 uppercase tracking-wide">Safety</p>
        <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-white">
          You decide who can reach you, and how
        </h2>
        <p class="mt-3 text-indigo-100 max-w-xl mx-auto">
          Blocking takes one tap and needs no explanation. Reports go to a real
          person, and whatever they decide gets written to an audit log.
        </p>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="max-w-6xl mx-auto px-6 pb-20 text-center">
      <p class="text-2xl font-bold text-gray-900">Got an event coming up?</p>
      <RouterLink
        :to="userStore.user ? dashboardLink : '/register'"
        class="mt-6 inline-flex items-center px-6 py-3 bg-accent-500 rounded-xl font-semibold text-white shadow-sm hover:bg-accent-600 hover:shadow-md transition"
      >
        {{ userStore.user ? "Go to dashboard" : "Create your free account" }}
      </RouterLink>
    </section>

    <footer class="border-t border-gray-100 py-8 text-center text-sm text-gray-500">
      Event Networking
    </footer>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore.js";
import { useUserStore } from "../stores/userStore.js";

const authStore = useAuthStore();
const userStore = useUserStore();
const router = useRouter();

const isAdmin = computed(() => Boolean(userStore.user?.is_admin));
const dashboardLink = computed(() => (isAdmin.value ? "/admin" : "/dashboard"));

async function handleLogout() {
  try {
    await authStore.logout();
  } catch (error) {
    // logout() clears the local session in its own finally block even when
    // the request fails, so there's nothing left to recover from here.
  }
  router.push("/");
}

const steps = [
  {
    title: "Find something worth going to",
    description:
      "Filter by industry, date, price, and format. Accessibility needs are a filter too, not a footnote.",
  },
  {
    title: "Answer a few questions",
    description:
      "One-to-one or small group? Happy to be approached, or would you rather message first? Your answers drive who you're shown.",
  },
  {
    title: "Talk before you turn up",
    description:
      "Message the people you matched with in the days beforehand, so nobody arrives to a room full of strangers.",
  },
];

const features = [
  {
    emoji: "🎯",
    title: "Scores that explain themselves",
    description:
      "Every match lists the specific things you have in common. If the reasoning looks wrong to you, ignore it.",
    badgeClass: "bg-indigo-50",
  },
  {
    emoji: "💬",
    title: "Messaging that starts itself",
    description:
      "Chat arrives live. And if you're staring at an empty box, there are openers you can borrow.",
    badgeClass: "bg-accent-50",
  },
  {
    emoji: "🕊️",
    title: "Say how you want to be approached",
    description:
      "Text only. No spontaneous calls. Ask before adding me to a group. People see your boundaries before they message.",
    badgeClass: "bg-emerald-50",
  },
  {
    emoji: "♿",
    title: "Accessibility you can filter on",
    description:
      "Wheelchair access, ASL interpretation, captioning, a quiet room — searchable, not buried in the description.",
    badgeClass: "bg-indigo-50",
  },
  {
    emoji: "🛡️",
    title: "Blocking and reporting that work",
    description:
      "Block anyone instantly. Reports go to a real moderation queue, and every action taken is logged.",
    badgeClass: "bg-accent-50",
  },
  {
    emoji: "✅",
    title: "Organisers are approved first",
    description:
      "Anyone can ask to run events, but an admin reviews the request before a single listing goes up.",
    badgeClass: "bg-emerald-50",
  },
];
</script>
