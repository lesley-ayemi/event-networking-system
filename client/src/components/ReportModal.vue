<template>
  <div
    class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    @click.self="close"
    @keydown.esc="close"
  >
    <div
      ref="panel"
      role="dialog"
      aria-modal="true"
      aria-labelledby="report-modal-title"
      tabindex="-1"
      class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-lg w-full max-w-sm p-6 focus:outline-none"
    >
      <h2 id="report-modal-title" class="text-base font-semibold text-gray-900 mb-1">{{ title }}</h2>
      <p class="text-sm text-gray-500 mb-4">
        Reports are reviewed by our team. This won't notify the other person.
      </p>

      <template v-if="!submitted">
        <div>
          <InputLabel for-id="report-reason" value="Reason" />
          <Select id="report-reason" v-model="reason">
            <option v-for="option in REPORT_REASONS" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </Select>
        </div>

        <div class="mt-3">
          <InputLabel for-id="report-details" value="Details (optional)" />
          <Textarea id="report-details" v-model="details" rows="3" />
        </div>

        <InputError :message="errorMessage" />

        <div class="flex items-center justify-end gap-3 mt-5">
          <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
          <PrimaryButton type="button" :disabled="isSubmitting" @click="submit">
            {{ isSubmitting ? "Submitting…" : "Submit report" }}
          </PrimaryButton>
        </div>
      </template>

      <template v-else>
        <p class="text-sm text-gray-700">Thanks — your report has been submitted.</p>
        <div class="flex items-center justify-end mt-5">
          <PrimaryButton type="button" @click="close">Close</PrimaryButton>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from "vue";
import InputLabel from "./InputLabel.vue";
import Select from "./Select.vue";
import Textarea from "./Textarea.vue";
import InputError from "./InputError.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";
import { useReportStore } from "../stores/reportStore.js";
import { getApiError } from "../services/apiError.js";
import { REPORT_REASONS } from "../constants/reportReasons.js";

const props = defineProps({
  title: { type: String, required: true },
  reportableType: { type: String, required: true },
  reportableId: { type: [Number, String], required: true },
});

const emit = defineEmits(["close", "submitted"]);

const reportStore = useReportStore();
const panel = ref(null);

onMounted(async () => {
  await nextTick();
  panel.value?.focus();
});

const reason = ref(REPORT_REASONS[0].value);
const details = ref("");
const isSubmitting = ref(false);
const errorMessage = ref("");
const submitted = ref(false);

async function submit() {
  isSubmitting.value = true;
  errorMessage.value = "";
  try {
    await reportStore.submitReport({
      reportableType: props.reportableType,
      reportableId: props.reportableId,
      reason: reason.value,
      details: details.value.trim(),
    });
    submitted.value = true;
    emit("submitted");
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't submit that report. Please try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}

function close() {
  emit("close");
}
</script>
