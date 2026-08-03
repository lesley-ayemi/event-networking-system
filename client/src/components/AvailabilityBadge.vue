<template>
  <span
    v-if="label"
    class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full"
    :class="colorClasses"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClasses" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from "vue";
import { availabilityLabel } from "../constants/conversationTools.js";

const props = defineProps({
  status: { type: String, default: "" },
});

const label = computed(() => availabilityLabel(props.status));

const STYLES = {
  available: { color: "bg-green-50 text-green-700", dot: "bg-green-500" },
  messages_welcome: { color: "bg-blue-50 text-blue-700", dot: "bg-blue-500" },
  prefer_later: { color: "bg-amber-50 text-amber-700", dot: "bg-amber-500" },
  observing: { color: "bg-gray-100 text-gray-600", dot: "bg-gray-400" },
  unavailable: { color: "bg-red-50 text-red-700", dot: "bg-red-400" },
};

const colorClasses = computed(() => STYLES[props.status]?.color ?? STYLES.observing.color);
const dotClasses = computed(() => STYLES[props.status]?.dot ?? STYLES.observing.dot);
</script>
