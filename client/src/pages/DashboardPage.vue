<template>
  <DefaultLayout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-2xl px-5 sm:px-6 py-6 shadow-sm">
      <h1 class="text-xl font-bold text-white">
        Welcome back{{ userStore.user?.first_name ? `, ${userStore.user.first_name}` : "" }}
      </h1>
      <InteractionStatus v-if="userStore.user" :status="userStore.user.availability_status" dark />
    </div>

    <div class="space-y-8">
      <section>
        <ProfileProgress v-if="userStore.user" :user="userStore.user" />
      </section>

      <section>
        <h2 class="text-base font-semibold text-gray-900 mb-3">Upcoming events</h2>
        <p v-if="eventsStore.isLoadingMyEvents" class="text-sm text-gray-500">Loading…</p>
        <p v-else-if="eventsStore.myEventsError" class="text-sm text-red-600">{{ eventsStore.myEventsError }}</p>
        <p v-else-if="upcomingEvents.length === 0" class="text-sm text-gray-500">
          You're not registered for any upcoming events. Browse
          <RouterLink to="/events" class="underline text-gray-700 hover:text-gray-900">events</RouterLink>
          to find one.
        </p>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <UpcomingEventCard v-for="event in upcomingEvents" :key="event.id" :event="event" />
        </div>
      </section>

      <section>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-semibold text-gray-900">Saved events</h2>
          <RouterLink
            v-if="bookmarkStore.bookmarkedEvents.length > 0"
            to="/saved-events"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
          >
            View all
          </RouterLink>
        </div>
        <p v-if="bookmarkStore.isLoading" class="text-sm text-gray-500">Loading…</p>
        <p v-else-if="bookmarkStore.error" class="text-sm text-red-600">{{ bookmarkStore.error }}</p>
        <p v-else-if="bookmarkStore.bookmarkedEvents.length === 0" class="text-sm text-gray-500">
          You haven't saved any events yet. Browse
          <RouterLink to="/events" class="underline text-gray-700 hover:text-gray-900">events</RouterLink>
          and tap the bookmark icon to save one here.
        </p>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <SavedEventCard v-for="event in bookmarkStore.bookmarkedEvents.slice(0, 4)" :key="event.id" :event="event" />
        </div>
      </section>

      <section v-if="eventsStore.recommendedEvents.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-semibold text-gray-900">Recommended for you</h2>
          <RouterLink to="/events" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Browse all</RouterLink>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <UpcomingEventCard v-for="event in eventsStore.recommendedEvents" :key="event.id" :event="event" />
        </div>
      </section>

      <section v-if="topMatches.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-semibold text-gray-900">Compatible attendees</h2>
          <RouterLink to="/matches" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all</RouterLink>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <MatchCard v-for="match in topMatches" :key="match.user.id" :match="match" />
        </div>
      </section>

      <section v-if="friendsStore.incomingRequests.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-semibold text-gray-900">Pending friend requests</h2>
          <RouterLink to="/friends" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all</RouterLink>
        </div>
        <div class="space-y-3">
          <FriendRequestCard v-for="request in friendsStore.incomingRequests.slice(0, 3)" :key="request.id" :request="request" />
        </div>
      </section>

      <section v-if="recentConversations.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-semibold text-gray-900">Recent messages</h2>
          <RouterLink to="/messages" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all</RouterLink>
        </div>
        <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
          <ConversationPreview v-for="conversation in recentConversations" :key="conversation.id" :conversation="conversation" />
        </div>
      </section>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import InteractionStatus from "../components/InteractionStatus.vue";
import ProfileProgress from "../components/ProfileProgress.vue";
import UpcomingEventCard from "../components/UpcomingEventCard.vue";
import SavedEventCard from "../components/SavedEventCard.vue";
import MatchCard from "../components/MatchCard.vue";
import FriendRequestCard from "../components/FriendRequestCard.vue";
import ConversationPreview from "../components/ConversationPreview.vue";
import { useUserStore } from "../stores/userStore.js";
import { useEventsStore } from "../stores/eventsStore.js";
import { useMatchesStore } from "../stores/matchesStore.js";
import { useFriendsStore } from "../stores/friendsStore.js";
import { useConversationsStore } from "../stores/conversationsStore.js";
import { useBookmarkStore } from "../stores/bookmarkStore.js";

const userStore = useUserStore();
const eventsStore = useEventsStore();
const matchesStore = useMatchesStore();
const friendsStore = useFriendsStore();
const conversationsStore = useConversationsStore();
const bookmarkStore = useBookmarkStore();

const upcomingEvents = computed(() => {
  const now = new Date();
  return eventsStore.myEvents.filter((event) => new Date(event.starts_at) >= now).slice(0, 4);
});

const topMatches = computed(() => matchesStore.matches.slice(0, 3));
const recentConversations = computed(() => conversationsStore.conversations.slice(0, 3));

onMounted(() => {
  eventsStore.fetchMyEvents();
  bookmarkStore.fetchBookmarks();
  eventsStore.fetchRecommendedEvents(userStore.user?.industry);
  matchesStore.fetchMatches();
  friendsStore.fetchAll();
  conversationsStore.fetchConversations();
});
</script>
