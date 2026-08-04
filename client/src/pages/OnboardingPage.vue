<template>
  <DefaultLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-lg font-medium text-gray-900 mb-6">Complete your profile</h1>

      <OnboardingProgressBar :steps="steps" :current-step="currentStep" />

      <div class="bg-white shadow-md rounded-lg px-6 py-6">
        <!-- Step 1: Personal information -->
        <section v-if="currentStep === 1">
          <h2 class="text-base font-semibold text-gray-900 mb-4">Personal information</h2>

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
              <label class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 cursor-pointer">
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

          <InputError :message="step1Error" />

          <div class="flex items-center justify-end mt-6">
            <PrimaryButton :disabled="isSavingStep1" @click="saveStep1">
              {{ isSavingStep1 ? "Saving…" : "Continue" }}
            </PrimaryButton>
          </div>
        </section>

        <!-- Step 2: Communication preferences + comfort settings -->
        <section v-if="currentStep === 2">
          <h2 class="text-base font-semibold text-gray-900 mb-1">Communication preferences</h2>
          <p class="text-sm text-gray-500 mb-4">How would you like to connect with other attendees?</p>

          <div class="space-y-3">
            <label class="flex items-center">
              <Checkbox v-model="preferences.one_to_one" />
              <span class="ms-2 text-sm text-gray-700">One-to-one conversations</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="preferences.small_groups" />
              <span class="ms-2 text-sm text-gray-700">Small groups</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="preferences.virtual_interaction" />
              <span class="ms-2 text-sm text-gray-700">Virtual interaction</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="preferences.text_communication" />
              <span class="ms-2 text-sm text-gray-700">Text communication</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="preferences.meet_before_event" />
              <span class="ms-2 text-sm text-gray-700">Meeting before an event</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="preferences.observe_first" />
              <span class="ms-2 text-sm text-gray-700">Observe-first participation</span>
            </label>
          </div>

          <h2 class="text-base font-semibold text-gray-900 mt-8 mb-1">Comfort settings</h2>
          <p class="text-sm text-gray-500 mb-4">Fine-tune how much control you have over matching and outreach.</p>

          <div>
            <InputLabel for-id="max_group_size" value="Maximum preferred group size" />
            <TextInput id="max_group_size" v-model.number="comfort.max_group_size" type="number" min="2" max="50" class="sm:w-40" />
          </div>

          <div class="space-y-3 mt-4">
            <label class="flex items-center">
              <Checkbox v-model="comfort.allow_message_first" />
              <span class="ms-2 text-sm text-gray-700">Other users may message me first</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="comfort.auto_matching" />
              <span class="ms-2 text-sm text-gray-700">Suggest automatic matches for me</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="comfort.pre_event_introductions" />
              <span class="ms-2 text-sm text-gray-700">Send pre-event introductions</span>
            </label>
            <label class="flex items-center">
              <Checkbox v-model="comfort.event_reminders" />
              <span class="ms-2 text-sm text-gray-700">Send event reminders</span>
            </label>
          </div>

          <InputError :message="step2Error" />

          <div class="flex items-center justify-between mt-6">
            <SecondaryButton @click="currentStep = 1">Back</SecondaryButton>
            <PrimaryButton :disabled="isSavingStep2" @click="saveStep2">
              {{ isSavingStep2 ? "Saving…" : "Continue" }}
            </PrimaryButton>
          </div>
        </section>

        <!-- Step 3: Compatibility quiz -->
        <section v-if="currentStep === 3">
          <h2 class="text-base font-semibold text-gray-900 mb-1">Compatibility quiz</h2>
          <p class="text-sm text-gray-500 mb-6">
            A short quiz helps us suggest people you're likely to click with. You can take it now or skip it and come back later.
          </p>

          <RouterLink
            to="/quiz"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
          >
            Take the quiz
          </RouterLink>

          <div class="flex items-center justify-between mt-6">
            <SecondaryButton @click="currentStep = 2">Back</SecondaryButton>
            <PrimaryButton :disabled="isCompleting" @click="completeOnboarding">
              {{ isCompleting ? "Saving…" : "Continue" }}
            </PrimaryButton>
          </div>
        </section>

        <!-- Step 4: Complete -->
        <section v-if="currentStep === 4">
          <h2 class="text-base font-semibold text-gray-900 mb-1">You're all set</h2>
          <p class="text-sm text-gray-500 mb-6">Your profile is ready. You can update any of this later from your profile page.</p>

          <RouterLink
            to="/dashboard"
            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
          >
            Go to your dashboard
          </RouterLink>
        </section>
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import OnboardingProgressBar from "../components/OnboardingProgressBar.vue";
import InputLabel from "../components/InputLabel.vue";
import TextInput from "../components/TextInput.vue";
import Textarea from "../components/Textarea.vue";
import InputError from "../components/InputError.vue";
import Checkbox from "../components/Checkbox.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import SecondaryButton from "../components/SecondaryButton.vue";
import { useUserStore } from "../stores/userStore.js";
import { getApiError } from "../services/apiError.js";

const userStore = useUserStore();

const steps = ["Profile", "Preferences", "Compatibility Quiz", "Complete"];
const currentStep = ref(1);

const firstName = ref(userStore.user?.first_name ?? "");
const lastName = ref(userStore.user?.last_name ?? "");
const jobTitle = ref(userStore.user?.job_title ?? "");
const industry = ref(userStore.user?.industry ?? "");
const bio = ref(userStore.user?.bio ?? "");
const networkingGoals = ref(userStore.user?.networking_goals ?? "");

const preferences = reactive({
  one_to_one: userStore.user?.interaction_preferences?.one_to_one ?? true,
  small_groups: userStore.user?.interaction_preferences?.small_groups ?? false,
  virtual_interaction: userStore.user?.interaction_preferences?.virtual_interaction ?? false,
  text_communication: userStore.user?.interaction_preferences?.text_communication ?? true,
  meet_before_event: userStore.user?.interaction_preferences?.meet_before_event ?? false,
  observe_first: userStore.user?.interaction_preferences?.observe_first ?? false,
});

const comfort = reactive({
  max_group_size: userStore.user?.comfort_settings?.max_group_size ?? 4,
  allow_message_first: userStore.user?.comfort_settings?.allow_message_first ?? true,
  auto_matching: userStore.user?.comfort_settings?.auto_matching ?? true,
  pre_event_introductions: userStore.user?.comfort_settings?.pre_event_introductions ?? true,
  event_reminders: userStore.user?.comfort_settings?.event_reminders ?? true,
});

const initials = computed(() => `${firstName.value.charAt(0)}${lastName.value.charAt(0)}`.toUpperCase());

const isSavingStep1 = ref(false);
const step1Error = ref("");
const isSavingStep2 = ref(false);
const step2Error = ref("");
const isUploadingPhoto = ref(false);
const photoError = ref("");
const isCompleting = ref(false);

async function saveStep1() {
  step1Error.value = "";
  isSavingStep1.value = true;
  try {
    await userStore.updateProfile({
      first_name: firstName.value,
      last_name: lastName.value,
      job_title: jobTitle.value,
      industry: industry.value,
      bio: bio.value,
      networking_goals: networkingGoals.value,
    });
    currentStep.value = 2;
  } catch (error) {
    step1Error.value = getApiError(error, "We couldn't save your profile. Please try again.").message;
  } finally {
    isSavingStep1.value = false;
  }
}

async function saveStep2() {
  step2Error.value = "";
  isSavingStep2.value = true;
  try {
    await userStore.updateProfile({
      interaction_preferences: { ...preferences },
      comfort_settings: { ...comfort },
    });
    currentStep.value = 3;
  } catch (error) {
    step2Error.value = getApiError(error, "We couldn't save your preferences. Please try again.").message;
  } finally {
    isSavingStep2.value = false;
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

async function completeOnboarding() {
  isCompleting.value = true;
  try {
    await userStore.updateProfile({ onboarding_completed: true });
  } catch (error) {
    // Non-blocking — the user can still finish; onboarding_completed can be retried from their profile page.
  } finally {
    isCompleting.value = false;
    currentStep.value = 4;
  }
}
</script>
