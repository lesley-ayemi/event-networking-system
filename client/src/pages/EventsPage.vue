<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Events</h1>

    <form @submit.prevent="applyFilters" class="bg-white shadow-sm rounded-lg px-6 py-5 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <InputLabel for-id="filter_date" value="Date" />
          <TextInput id="filter_date" v-model="filters.date" type="date" />
        </div>
        <div>
          <InputLabel for-id="filter_industry" value="Industry" />
          <TextInput id="filter_industry" v-model="filters.industry" type="text" placeholder="e.g. Technology" />
        </div>
        <div>
          <InputLabel for-id="filter_location" value="Location" />
          <TextInput id="filter_location" v-model="filters.location" type="text" placeholder="e.g. San Francisco" />
        </div>
        <div>
          <InputLabel for-id="filter_format" value="Virtual or physical" />
          <select
            id="filter_format"
            v-model="filters.format"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full text-sm"
          >
            <option value="">Any</option>
            <option value="virtual">Virtual</option>
            <option value="physical">Physical</option>
          </select>
        </div>
        <div>
          <InputLabel for-id="filter_price" value="Free or paid" />
          <select
            id="filter_price"
            v-model="filters.price"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full text-sm"
          >
            <option value="">Any</option>
            <option value="free">Free</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        <div class="flex items-end gap-4">
          <label class="flex items-center">
            <Checkbox v-model="filters.one_to_one" />
            <span class="ms-2 text-sm text-gray-700">One-to-one</span>
          </label>
          <label class="flex items-center">
            <Checkbox v-model="filters.small_group" />
            <span class="ms-2 text-sm text-gray-700">Small group</span>
          </label>
        </div>
        <div class="sm:col-span-2 lg:col-span-2">
          <InputLabel value="Accessibility" />
          <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
            <label v-for="option in accessibilityOptions" :key="option.value" class="flex items-center">
              <Checkbox v-model="filters.accessibility[option.value]" />
              <span class="ms-2 text-sm text-gray-700">{{ option.label }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 mt-4">
        <SecondaryButton type="button" @click="clearFilters">Clear</SecondaryButton>
        <PrimaryButton>Apply filters</PrimaryButton>
      </div>
    </form>

    <p v-if="eventsStore.error" class="text-sm text-red-600 mb-4">{{ eventsStore.error }}</p>

    <p v-if="!eventsStore.isLoading && eventsStore.events.length === 0" class="text-sm text-gray-500">
      No events match your filters right now.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <EventCard v-for="event in eventsStore.events" :key="event.id" :event="event" />
    </div>

    <div v-if="eventsStore.pagination && eventsStore.pagination.last_page > 1" class="flex items-center justify-between mt-6">
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ eventsStore.pagination.current_page }} of {{ eventsStore.pagination.last_page }}
      </span>
      <SecondaryButton :disabled="currentPage === eventsStore.pagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </SecondaryButton>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import EventCard from "../components/EventCard.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import Checkbox from "../components/Checkbox.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useEventsStore } from "../stores/eventsStore.js";

const eventsStore = useEventsStore();

const accessibilityOptions = [
  { value: "wheelchair_accessible", label: "Wheelchair accessible" },
  { value: "asl_interpretation", label: "ASL interpretation" },
  { value: "quiet_room", label: "Quiet room" },
  { value: "captioning", label: "Captioning" },
];

const filters = reactive({
  date: "",
  industry: "",
  location: "",
  format: "",
  price: "",
  one_to_one: false,
  small_group: false,
  accessibility: {
    wheelchair_accessible: false,
    asl_interpretation: false,
    quiet_room: false,
    captioning: false,
  },
});

const currentPage = ref(1);

function buildParams() {
  const params = { page: currentPage.value };
  if (filters.date) params.date = filters.date;
  if (filters.industry) params.industry = filters.industry;
  if (filters.location) params.location = filters.location;
  if (filters.format) params.format = filters.format;
  if (filters.price) params.price = filters.price;
  if (filters.one_to_one) params.one_to_one = 1;
  if (filters.small_group) params.small_group = 1;

  const selectedAccessibility = Object.entries(filters.accessibility)
    .filter(([, checked]) => checked)
    .map(([value]) => value);
  if (selectedAccessibility.length > 0) {
    params.accessibility = selectedAccessibility.join(",");
  }

  return params;
}

function applyFilters() {
  currentPage.value = 1;
  eventsStore.fetchEvents(buildParams());
}

function clearFilters() {
  filters.date = "";
  filters.industry = "";
  filters.location = "";
  filters.format = "";
  filters.price = "";
  filters.one_to_one = false;
  filters.small_group = false;
  Object.keys(filters.accessibility).forEach((key) => {
    filters.accessibility[key] = false;
  });
  currentPage.value = 1;
  eventsStore.fetchEvents(buildParams());
}

function goToPage(page) {
  currentPage.value = page;
  eventsStore.fetchEvents(buildParams());
}

onMounted(() => {
  eventsStore.fetchEvents(buildParams());
});
</script>
