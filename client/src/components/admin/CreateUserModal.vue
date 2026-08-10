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
      aria-labelledby="create-user-modal-title"
      tabindex="-1"
      class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-lg w-full max-w-sm p-6 focus:outline-none"
    >
      <h2 id="create-user-modal-title" class="text-base font-semibold text-gray-900 mb-4">Create user</h2>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <InputLabel for-id="new_first_name" value="First name" />
            <TextInput id="new_first_name" v-model="form.first_name" type="text" class="w-full" />
          </div>
          <div>
            <InputLabel for-id="new_last_name" value="Last name" />
            <TextInput id="new_last_name" v-model="form.last_name" type="text" class="w-full" />
          </div>
        </div>

        <div>
          <InputLabel for-id="new_email" value="Email" />
          <TextInput id="new_email" v-model="form.email" type="email" class="w-full" />
        </div>

        <div>
          <InputLabel for-id="new_password" value="Password" />
          <TextInput id="new_password" v-model="form.password" type="password" class="w-full" />
        </div>

        <div>
          <InputLabel for-id="new_password_confirmation" value="Confirm password" />
          <TextInput id="new_password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" />
        </div>

        <label class="flex items-center gap-2">
          <Checkbox v-model="form.is_admin" />
          <span class="text-sm text-gray-700">Grant admin access</span>
        </label>

        <InputError :message="errorMessage" />

        <div class="flex items-center justify-end gap-3 pt-1">
          <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
          <PrimaryButton type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? "Creating…" : "Create user" }}
          </PrimaryButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref } from "vue";
import InputLabel from "../InputLabel.vue";
import TextInput from "../TextInput.vue";
import Checkbox from "../Checkbox.vue";
import InputError from "../InputError.vue";
import PrimaryButton from "../PrimaryButton.vue";
import SecondaryButton from "../SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { getApiError } from "../../services/apiError.js";

const emit = defineEmits(["close", "created"]);

const adminStore = useAdminStore();
const panel = ref(null);

onMounted(async () => {
  await nextTick();
  panel.value?.focus();
});

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  password: "",
  password_confirmation: "",
  is_admin: false,
});
const isSubmitting = ref(false);
const errorMessage = ref("");

async function submit() {
  isSubmitting.value = true;
  errorMessage.value = "";
  try {
    const user = await adminStore.createUser({ ...form });
    emit("created", user);
  } catch (error) {
    errorMessage.value = getApiError(error, "We couldn't create that user. Please try again.").message;
  } finally {
    isSubmitting.value = false;
  }
}

function close() {
  emit("close");
}
</script>
