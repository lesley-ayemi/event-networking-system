<template>
  <DefaultLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-lg font-medium text-gray-900 mb-2">Compatibility quiz</h1>
      <p class="text-sm text-gray-500 mb-6">
        These {{ questions.length }} quick questions help us suggest people you're likely to click with.
      </p>

      <div v-if="submitted" class="bg-white shadow-md ring-1 ring-gray-100 rounded-xl px-6 py-6">
        <h2 class="text-base font-semibold text-gray-900 mb-1">You're all set</h2>
        <p class="text-sm text-gray-500 mb-4">
          Thanks for answering — we'll use this to suggest people you're likely to click with.
        </p>
        <RouterLink
          to="/dashboard"
          class="inline-flex items-center px-4 py-2.5 bg-indigo-600 rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 transition"
        >
          Go to your dashboard
        </RouterLink>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="bg-white shadow-md ring-1 ring-gray-100 rounded-xl px-6 py-6 space-y-6">
        <QuizQuestion
          v-for="(question, index) in questions"
          :key="question.key"
          :question="question"
          :number="index + 1"
          v-model="answers[question.key]"
        />

        <InputError :message="error" />

        <PrimaryButton :disabled="isSubmitting">
          {{ isSubmitting ? "Saving…" : "Submit quiz" }}
        </PrimaryButton>
      </form>
    </div>
  </DefaultLayout>
</template>

<script setup>
import { reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";
import QuizQuestion from "../components/QuizQuestion.vue";
import InputError from "../components/InputError.vue";
import PrimaryButton from "../components/PrimaryButton.vue";
import { useUserStore } from "../stores/userStore.js";

const userStore = useUserStore();

const questions = [
  {
    key: "oneToOnePreference",
    text: "Do you prefer one-to-one or group conversations?",
    options: [
      { value: 1, label: "Strongly prefer group conversations" },
      { value: 2, label: "Somewhat prefer group conversations" },
      { value: 3, label: "No strong preference" },
      { value: 4, label: "Somewhat prefer one-to-one" },
      { value: 5, label: "Strongly prefer one-to-one" },
    ],
  },
  {
    key: "preferredGroupSize",
    text: "How large should a comfortable group be?",
    options: [
      { value: 1, label: "Just me and one other person" },
      { value: 2, label: "A small group (3-4 people)" },
      { value: 3, label: "A medium group (5-6 people)" },
      { value: 4, label: "A larger group (7-10 people)" },
      { value: 5, label: "The bigger the better" },
    ],
  },
  {
    key: "messageBeforeMeeting",
    text: "Do you prefer messaging before meeting someone?",
    options: [
      { value: 1, label: "Never — I'd rather meet directly" },
      { value: 2, label: "Rarely" },
      { value: 3, label: "Sometimes" },
      { value: 4, label: "Usually" },
      { value: 5, label: "Always — I like to message first" },
    ],
  },
  {
    key: "structuredConversation",
    text: "Do you prefer structured or spontaneous conversations?",
    options: [
      { value: 1, label: "Completely spontaneous" },
      { value: 2, label: "Mostly spontaneous" },
      { value: 3, label: "A mix of both" },
      { value: 4, label: "Mostly structured" },
      { value: 5, label: "Clearly structured with a topic or agenda" },
    ],
  },
  {
    key: "responseSpeed",
    text: "How quickly do you normally respond to messages?",
    options: [
      { value: 1, label: "It can take me a while" },
      { value: 2, label: "Within a day or so" },
      { value: 3, label: "Within a few hours" },
      { value: 4, label: "Within the hour" },
      { value: 5, label: "Almost immediately" },
    ],
  },
  {
    key: "virtualPreference",
    text: "Would you prefer virtual or face-to-face meetings?",
    options: [
      { value: 1, label: "Strongly prefer face-to-face" },
      { value: 2, label: "Somewhat prefer face-to-face" },
      { value: 3, label: "No strong preference" },
      { value: 4, label: "Somewhat prefer virtual" },
      { value: 5, label: "Strongly prefer virtual" },
    ],
  },
  {
    key: "networkingGoal",
    text: "What is your main networking goal?",
    options: [
      { value: 1, label: "Finding clients or customers" },
      { value: 2, label: "Finding collaborators or co-founders" },
      { value: 3, label: "Finding mentors or mentees" },
      { value: 4, label: "Finding a job or hiring talent" },
      { value: 5, label: "General networking and making friends" },
    ],
  },
  {
    key: "industryInterest",
    text: "Which industries interest you most?",
    options: [
      { value: 1, label: "Technology" },
      { value: 2, label: "Finance" },
      { value: 3, label: "Healthcare" },
      { value: 4, label: "Design" },
      { value: 5, label: "Education" },
    ],
  },
  {
    key: "observeFirstPreference",
    text: "Would you rather listen first or begin conversations?",
    options: [
      { value: 1, label: "I like to start conversations" },
      { value: 2, label: "I usually start, but not always" },
      { value: 3, label: "It depends on the setting" },
      { value: 4, label: "I usually wait and observe first" },
      { value: 5, label: "I strongly prefer to listen first" },
    ],
  },
  {
    key: "conversationLengthPreference",
    text: "How long would you prefer an initial conversation to last?",
    options: [
      { value: 1, label: "Just a few minutes" },
      { value: 2, label: "About 10-15 minutes" },
      { value: 3, label: "About 20-30 minutes" },
      { value: 4, label: "About 45 minutes" },
      { value: 5, label: "An hour or more" },
    ],
  },
];

const existingAnswers = userStore.user?.quiz_answers ?? {};
const answers = reactive(
  Object.fromEntries(questions.map((question) => [question.key, existingAnswers[question.key] ?? 3]))
);

const isSubmitting = ref(false);
const error = ref("");
const submitted = ref(false);

async function handleSubmit() {
  error.value = "";
  isSubmitting.value = true;
  try {
    await userStore.updateQuizAnswers({ ...answers });
    submitted.value = true;
  } catch (err) {
    error.value = "We couldn't save your answers. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>
