<template>
  <DefaultLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Profile</h1>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6 max-w-xl">
      <h2 class="text-base font-semibold text-gray-900 mb-1">Profile details</h2>
      <p class="text-sm text-gray-500 mb-4">This is what other attendees see when they view your profile.</p>

      <div class="flex items-center gap-4 mb-6">
        <img
          v-if="userStore.user?.profile_image"
          :src="userStore.user.profile_image"
          alt="Profile photo"
          class="w-16 h-16 rounded-full object-cover bg-gray-100"
        />
        <div v-else class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg font-semibold">
          {{ initials }}
        </div>
        <div>
          <label class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-xl font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 transition cursor-pointer">
            {{ isUploadingPhoto ? "Uploading…" : "Upload photo" }}
            <input type="file" accept="image/*" class="hidden" :disabled="isUploadingPhoto" @change="handlePhotoChange" />
          </label>
          <InputError :message="photoError" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <InputLabel for-id="first_name" value="First name" />
          <TextInput id="first_name" v-model="firstName" type="text" required />
        </div>
        <div>
          <InputLabel for-id="last_name" value="Last name" />
          <TextInput id="last_name" v-model="lastName" type="text" required />
        </div>
      </div>

      <div class="mt-4">
        <InputLabel for-id="job_title" value="Professional role" />
        <TextInput id="job_title" v-model="jobTitle" type="text" placeholder="e.g. Product Designer" />
      </div>

      <div class="mt-4">
        <InputLabel for-id="industry" value="Industry" />
        <TextInput id="industry" v-model="industry" type="text" placeholder="e.g. Technology" />
      </div>

      <div class="mt-4">
        <InputLabel for-id="bio" value="Short biography" />
        <Textarea id="bio" v-model="bio" rows="3" />
      </div>

      <div class="mt-4">
        <InputLabel for-id="networking_goals" value="Networking goals" />
        <Textarea id="networking_goals" v-model="networkingGoals" rows="3" placeholder="What are you hoping to get out of events?" />
      </div>

      <p v-if="detailsMessage" class="text-sm text-gray-500 mt-6">{{ detailsMessage }}</p>
      <p v-if="detailsError" class="text-sm text-red-600 mt-6">{{ detailsError }}</p>

      <div class="mt-6">
        <PrimaryButton :disabled="isSavingDetails" @click="saveDetails">
          {{ isSavingDetails ? "Saving…" : "Save" }}
        </PrimaryButton>
      </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6 max-w-xl mt-6">
      <h2 class="text-base font-semibold text-gray-900 mb-1">Availability status</h2>
      <p class="text-sm text-gray-500 mb-4">Let people know how open you are to chatting right now.</p>

      <div class="space-y-3">
        <label v-for="status in AVAILABILITY_STATUSES" :key="status.value" class="flex items-center">
          <input
            type="radio"
            name="availability_status"
            :value="status.value"
            v-model="availabilityStatus"
            class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
          />
          <span class="ms-2 text-sm text-gray-700">{{ status.label }}</span>
        </label>
      </div>

      <h2 class="text-base font-semibold text-gray-900 mt-8 mb-1">Conversation boundaries</h2>
      <p class="text-sm text-gray-500 mb-4">Set expectations so conversations stay comfortable for you.</p>

      <div class="space-y-3">
        <label v-for="boundary in CONVERSATION_BOUNDARIES" :key="boundary.key" class="flex items-center">
          <Checkbox v-model="boundaries[boundary.key]" />
          <span class="ms-2 text-sm text-gray-700">{{ boundary.label }}</span>
        </label>
      </div>

      <p v-if="statusMessage" class="text-sm text-gray-500 mt-6">{{ statusMessage }}</p>
      <p v-if="errorMessage" class="text-sm text-red-600 mt-6">{{ errorMessage }}</p>

      <div class="mt-6">
        <PrimaryButton :disabled="isSaving" @click="save">{{ isSaving ? "Saving…" : "Save" }}</PrimaryButton>
      </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6 max-w-xl mt-6">
      <h2 class="text-base font-semibold text-gray-900 mb-1">Event organiser</h2>
      <p class="text-sm text-gray-500 mb-4">Approved organisers can create and publish events.</p>

      <p v-if="organiserStatus === 'approved'" class="text-sm text-green-700">
        You're an approved organiser.
      </p>
      <p v-else-if="organiserStatus === 'pending'" class="text-sm text-gray-600">
        Your request is pending review.
      </p>
      <template v-else>
        <p v-if="organiserStatus === 'rejected'" class="text-sm text-gray-600 mb-3">
          Your previous request wasn't approved. You can request again.
        </p>
        <SecondaryButton :disabled="isRequestingOrganiser" @click="requestOrganiser">
          {{ isRequestingOrganiser ? "Requesting…" : "Request organiser access" }}
        </SecondaryButton>
      </template>
      <p v-if="organiserError" class="text-sm text-red-600 mt-3">{{ organiserError }}</p>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import Checkbox from "../components/Checkbox.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import Textarea from "../components/Textarea.vue";
import InputError from "../components/InputError.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useUserStore } from "../stores/userStore.js";
import { AVAILABILITY_STATUSES, CONVERSATION_BOUNDARIES } from "../constants/conversationTools.js";
import { getApiError } from "../services/apiError.js";

const userStore = useUserStore();

const firstName = ref(userStore.user?.first_name ?? "");
const lastName = ref(userStore.user?.last_name ?? "");
const jobTitle = ref(userStore.user?.job_title ?? "");
const industry = ref(userStore.user?.industry ?? "");
const bio = ref(userStore.user?.bio ?? "");
const networkingGoals = ref(userStore.user?.networking_goals ?? "");

const initials = computed(() => `${firstName.value.charAt(0)}${lastName.value.charAt(0)}`.toUpperCase());

const isSavingDetails = ref(false);
const detailsMessage = ref("");
const detailsError = ref("");
const isUploadingPhoto = ref(false);
const photoError = ref("");

async function saveDetails() {
  isSavingDetails.value = true;
  detailsMessage.value = "";
  detailsError.value = "";
  try {
    await userStore.updateProfile({
      first_name: firstName.value,
      last_name: lastName.value,
      job_title: jobTitle.value,
      industry: industry.value,
      bio: bio.value,
      networking_goals: networkingGoals.value,
    });
    detailsMessage.value = "Saved.";
  } catch (error) {
    detailsError.value = getApiError(error, "We couldn't save your changes. Please try again.").message;
  } finally {
    isSavingDetails.value = false;
  }
}

async function handlePhotoChange(event) {
  const file = event.target.files[0];
  if (!file) {
    return;
  }
  photoError.value = "";
  isUploadingPhoto.value = true;
  try {
    await userStore.uploadProfilePhoto(file);
  } catch (error) {
    photoError.value = getApiError(error, "We couldn't upload that photo. Please try a smaller image file.").message;
  } finally {
    isUploadingPhoto.value = false;
  }
}

const availabilityStatus = ref(userStore.user?.availability_status ?? "available");
const boundaries = reactive(
  Object.fromEntries(
    CONVERSATION_BOUNDARIES.map((boundary) => [
      boundary.key,
      userStore.user?.conversation_boundaries?.[boundary.key] ?? false,
    ])
  )
);

const isSaving = ref(false);
const statusMessage = ref("");
const errorMessage = ref("");

async function save() {
  isSaving.value = true;
  statusMessage.value = "";
  errorMessage.value = "";
  try {
    await userStore.updateProfile({
      availability_status: availabilityStatus.value,
      conversation_boundaries: { ...boundaries },
    });
    statusMessage.value = "Saved.";
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't save your changes. Please try again.").message;
  } finally {
    isSaving.value = false;
  }
}

const organiserStatus = computed(() => userStore.user?.organiser_status ?? "none");
const isRequestingOrganiser = ref(false);
const organiserError = ref("");

async function requestOrganiser() {
  isRequestingOrganiser.value = true;
  organiserError.value = "";
  try {
    await userStore.requestOrganiserStatus();
  } catch (error) {
    organiserError.value = getApiError(error, "We couldn't submit that request. Please try again.").message;
  } finally {
    isRequestingOrganiser.value = false;
  }
}
</script>
