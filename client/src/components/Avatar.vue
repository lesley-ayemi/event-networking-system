<template>
  <img
    v-if="user.profile_image"
    :src="user.profile_image"
    alt=""
    class="rounded-full object-cover bg-gray-100 w-10 h-10 ring-2 ring-white shadow-sm"
  />
  <div
    v-else
    class="rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold shrink-0 w-10 h-10 ring-2 ring-white shadow-sm"
  >
    {{ initials }}
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  user: { type: Object, required: true },
});

const initials = computed(
  () => `${props.user.first_name?.charAt(0) ?? ""}${props.user.last_name?.charAt(0) ?? ""}`.toUpperCase()
);
</script>
