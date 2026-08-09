<template>
  <span
    v-if="label"
    class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full"
    :class="colorClasses"
    :title="tooltip"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClasses" />
    {{ label }}<span v-if="isStale" class="opacity-75"> · {{ relativeTime }}</span>
  </span>
</template>

<script setup>
import { computed } from "vue";
import { availabilityLabel } from "../constants/conversationTools.js";

// Status is self-reported with no live presence signal behind it, so a
// reading left untouched this long is treated as stale and shown muted
// with an age hint rather than implying it's current.
const STALE_THRESHOLD_DAYS = 14;

const props = defineProps({
  status: { type: String, default: "" },
  updatedAt: { type: [String, Date], default: null },
});

const label = computed(() => availabilityLabel(props.status));

const daysSinceUpdate = computed(() => {
  if (!props.updatedAt) {
    return null;
  }
  const updated = new Date(props.updatedAt);
  return (Date.now() - updated.getTime()) / (1000 * 60 * 60 * 24);
});

const isStale = computed(() => daysSinceUpdate.value !== null && daysSinceUpdate.value >= STALE_THRESHOLD_DAYS);

const relativeTime = computed(() => {
  if (daysSinceUpdate.value === null) {
    return "";
  }
  const days = Math.floor(daysSinceUpdate.value);
  if (days < 30) {
    return `${days}d ago`;
  }
  const months = Math.floor(days / 30);
  if (months < 12) {
    return `${months}mo ago`;
  }
  return `${Math.floor(months / 12)}y ago`;
});

const tooltip = computed(() => (props.updatedAt ? `Status set ${relativeTime.value}` : ""));

const STYLES = {
  available: { color: "bg-green-50 text-green-700", dot: "bg-green-500" },
  messages_welcome: { color: "bg-blue-50 text-blue-700", dot: "bg-blue-500" },
  prefer_later: { color: "bg-amber-50 text-amber-700", dot: "bg-amber-500" },
  observing: { color: "bg-gray-100 text-gray-600", dot: "bg-gray-400" },
  unavailable: { color: "bg-red-50 text-red-700", dot: "bg-red-400" },
};

const STALE_STYLE = { color: "bg-gray-100 text-gray-500", dot: "bg-gray-300" };

const colorClasses = computed(() =>
  isStale.value ? STALE_STYLE.color : (STYLES[props.status]?.color ?? STYLES.observing.color)
);
const dotClasses = computed(() =>
  isStale.value ? STALE_STYLE.dot : (STYLES[props.status]?.dot ?? STYLES.observing.dot)
);
</script>
