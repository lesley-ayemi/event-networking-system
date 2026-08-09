<template>
  <div class="bg-white">
    <header class="max-w-6xl mx-auto px-6 flex items-center justify-between h-16">
      <RouterLink to="/" class="text-lg font-bold text-gray-900 tracking-tight">
        Event<span class="text-indigo-600">Networking</span>
      </RouterLink>
      <nav class="flex items-center gap-4">
        <RouterLink to="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900">Log in</RouterLink>
        <RouterLink
          to="/register"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition"
        >
          Get started
        </RouterLink>
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
          Discover events, get matched with people you'll actually click with, and start the conversation
          before you ever have to make small talk in person.
        </p>
        <div class="mt-8 flex items-center justify-center gap-4">
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
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="max-w-6xl mx-auto px-6 py-16">
      <h2 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide text-center">How it works</h2>
      <p class="mt-2 text-2xl font-bold text-gray-900 text-center">Three steps to a genuine connection</p>

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
        <p class="text-2xl font-bold text-gray-900 text-center">Built for how real networking actually feels</p>
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
        <p class="text-sm font-semibold text-indigo-200 uppercase tracking-wide">Safety comes first</p>
        <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-white">
          Block, report, and set boundaries — always in your control
        </h2>
        <p class="mt-3 text-indigo-100 max-w-xl mx-auto">
          Every conversation respects the limits you set, and every report is reviewed by a real moderation team.
        </p>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="max-w-6xl mx-auto px-6 pb-20 text-center">
      <p class="text-2xl font-bold text-gray-900">Ready to meet people worth knowing?</p>
      <RouterLink
        to="/register"
        class="mt-6 inline-flex items-center px-6 py-3 bg-accent-500 rounded-xl font-semibold text-white shadow-sm hover:bg-accent-600 hover:shadow-md transition"
      >
        Create your free account
      </RouterLink>
    </section>

    <footer class="border-t border-gray-100 py-8 text-center text-sm text-gray-400">
      Event Networking
    </footer>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";

const steps = [
  {
    title: "Discover events",
    description: "Browse by industry, format, and interaction style to find events worth your time.",
  },
  {
    title: "Get matched",
    description: "A transparent compatibility score shows exactly why you and someone else might click.",
  },
  {
    title: "Connect on your terms",
    description: "Set your availability and boundaries, then message before you ever meet in person.",
  },
];

const features = [
  {
    emoji: "🎯",
    title: "Transparent matching",
    description: "See the real reasons behind every compatibility score — no black box.",
    badgeClass: "bg-indigo-50",
  },
  {
    emoji: "💬",
    title: "Real-time messaging",
    description: "Instant conversations with conversation starters when you don't know what to say.",
    badgeClass: "bg-accent-50",
  },
  {
    emoji: "🕊️",
    title: "Low-pressure by design",
    description: "Set your availability status and conversation boundaries so people know how to approach you.",
    badgeClass: "bg-emerald-50",
  },
  {
    emoji: "📅",
    title: "Event discovery",
    description: "Filter by accessibility needs, format, and group size, then bookmark what looks interesting.",
    badgeClass: "bg-indigo-50",
  },
  {
    emoji: "🛡️",
    title: "Built-in safety tools",
    description: "Block or report anyone in a couple of taps — every report reaches a real moderator.",
    badgeClass: "bg-accent-50",
  },
  {
    emoji: "✅",
    title: "Verified organisers",
    description: "Events are created by approved organisers, so listings stay trustworthy.",
    badgeClass: "bg-emerald-50",
  },
];
</script>
