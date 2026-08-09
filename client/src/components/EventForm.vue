<template>
  <form @submit.prevent="submit" class="space-y-5">
    <div>
      <InputLabel for-id="name" value="Event name" />
      <TextInput id="name" v-model="form.name" type="text" required />
    </div>

    <div>
      <InputLabel for-id="description" value="Description" />
      <Textarea id="description" v-model="form.description" rows="4" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <InputLabel for-id="starts_at" value="Starts at" />
        <TextInput id="starts_at" v-model="form.starts_at" type="datetime-local" required />
      </div>
      <div>
        <InputLabel for-id="ends_at" value="Ends at (optional)" />
        <TextInput id="ends_at" v-model="form.ends_at" type="datetime-local" />
      </div>
    </div>

    <label class="flex items-center">
      <Checkbox v-model="form.is_virtual" />
      <span class="ms-2 text-sm text-gray-700">This is a virtual event</span>
    </label>

    <div v-if="!form.is_virtual">
      <InputLabel for-id="location" value="Location" />
      <TextInput id="location" v-model="form.location" type="text" placeholder="e.g. San Francisco, CA" />
    </div>

    <div>
      <InputLabel for-id="industry" value="Industry" />
      <TextInput id="industry" v-model="form.industry" type="text" placeholder="e.g. Technology" />
    </div>

    <div>
      <InputLabel value="Interaction modes available" />
      <div class="mt-1 space-y-2">
        <label class="flex items-center">
          <Checkbox v-model="form.one_to_one_available" />
          <span class="ms-2 text-sm text-gray-700">One-to-one</span>
        </label>
        <label class="flex items-center">
          <Checkbox v-model="form.small_group_available" />
          <span class="ms-2 text-sm text-gray-700">Small group</span>
        </label>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="flex items-center">
          <Checkbox v-model="form.is_free" />
          <span class="ms-2 text-sm text-gray-700">This event is free</span>
        </label>
        <TextInput
          v-if="!form.is_free"
          v-model="form.price"
          type="number"
          min="0"
          step="0.01"
          placeholder="Price"
          class="mt-2"
        />
      </div>
      <div>
        <InputLabel for-id="capacity" value="Capacity (optional)" />
        <TextInput id="capacity" v-model="form.capacity" type="number" min="1" placeholder="No limit" />
      </div>
    </div>

    <div>
      <InputLabel value="Accessibility" />
      <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
        <label v-for="option in ACCESSIBILITY_OPTIONS" :key="option.value" class="flex items-center">
          <Checkbox v-model="form.accessibility_options[option.value]" />
          <span class="ms-2 text-sm text-gray-700">{{ option.label }}</span>
        </label>
      </div>
    </div>

    <InputError :message="errorMessage" />

    <div class="flex items-center justify-end gap-3">
      <SecondaryButton type="button" @click="$emit('cancel')">Cancel</SecondaryButton>
      <PrimaryButton :disabled="isSubmitting">{{ submitLabel(isSubmitting) }}</PrimaryButton>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from "vue";
import InputLabel from "./InputLabel.vue";
import TextInput from "./TextInput.vue";
import Textarea from "./Textarea.vue";
import Checkbox from "./Checkbox.vue";
import InputError from "./InputError.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";

const ACCESSIBILITY_OPTIONS = [
  { value: "wheelchair_accessible", label: "Wheelchair accessible" },
  { value: "asl_interpretation", label: "ASL interpretation" },
  { value: "quiet_room", label: "Quiet room" },
  { value: "captioning", label: "Captioning" },
];

function toDatetimeLocal(value) {
  if (!value) {
    return "";
  }
  const date = new Date(value);
  const pad = (n) => String(n).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

const props = defineProps({
  event: { type: Object, default: null },
  isSubmitting: { type: Boolean, default: false },
  errorMessage: { type: String, default: "" },
  submitLabel: { type: Function, default: (busy) => (busy ? "Saving…" : "Save") },
});

const emit = defineEmits(["submit", "cancel"]);

const form = reactive({
  name: props.event?.name ?? "",
  description: props.event?.description ?? "",
  starts_at: toDatetimeLocal(props.event?.starts_at),
  ends_at: toDatetimeLocal(props.event?.ends_at),
  is_virtual: props.event?.is_virtual ?? false,
  location: props.event?.location ?? "",
  industry: props.event?.industry ?? "",
  one_to_one_available: props.event?.one_to_one_available ?? true,
  small_group_available: props.event?.small_group_available ?? false,
  is_free: props.event?.is_free ?? true,
  price: props.event?.price ?? "",
  capacity: props.event?.capacity ?? "",
  accessibility_options: Object.fromEntries(
    ACCESSIBILITY_OPTIONS.map((option) => [option.value, props.event?.accessibility_options?.includes(option.value) ?? false])
  ),
});

function submit() {
  const selectedAccessibility = Object.entries(form.accessibility_options)
    .filter(([, checked]) => checked)
    .map(([value]) => value);

  emit("submit", {
    name: form.name,
    description: form.description || null,
    starts_at: form.starts_at,
    ends_at: form.ends_at || null,
    is_virtual: form.is_virtual,
    location: form.is_virtual ? null : form.location || null,
    industry: form.industry || null,
    one_to_one_available: form.one_to_one_available,
    small_group_available: form.small_group_available,
    is_free: form.is_free,
    price: form.is_free ? null : form.price || null,
    capacity: form.capacity || null,
    accessibility_options: selectedAccessibility,
  });
}
</script>
