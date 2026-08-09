<template>
  <AdminLayout>
    <RouterLink to="/admin/users" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to users</RouterLink>

    <p v-if="adminStore.isLoadingCurrentUser" class="text-sm text-gray-500 mt-4">Loading…</p>
    <p v-else-if="adminStore.currentUserError" class="text-sm text-red-600 mt-4">{{ adminStore.currentUserError }}</p>

    <div v-else-if="user" class="mt-4 space-y-6 max-w-xl">
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-lg font-medium text-gray-900 mr-2">{{ user.first_name }} {{ user.last_name }}</h1>
        <span v-if="user.deleted_at" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Deleted</span>
        <span v-if="user.is_suspended" class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-700">Suspended</span>
        <span v-if="user.is_admin" class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">Admin</span>
        <span v-if="user.organiser_status === 'approved'" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
          Organiser
        </span>
      </div>

      <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Profile details</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <InputLabel for-id="first_name" value="First name" />
            <TextInput id="first_name" v-model="form.first_name" type="text" class="w-full" />
          </div>
          <div>
            <InputLabel for-id="last_name" value="Last name" />
            <TextInput id="last_name" v-model="form.last_name" type="text" class="w-full" />
          </div>
        </div>

        <div class="mt-4">
          <InputLabel for-id="email" value="Email" />
          <TextInput id="email" v-model="form.email" type="email" class="w-full" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div>
            <InputLabel for-id="job_title" value="Professional role" />
            <TextInput id="job_title" v-model="form.job_title" type="text" class="w-full" />
          </div>
          <div>
            <InputLabel for-id="industry" value="Industry" />
            <TextInput id="industry" v-model="form.industry" type="text" class="w-full" />
          </div>
        </div>

        <div class="mt-4">
          <InputLabel for-id="bio" value="Short biography" />
          <Textarea id="bio" v-model="form.bio" rows="3" class="w-full" />
        </div>

        <p v-if="saveMessage" class="text-sm text-gray-500 mt-4">{{ saveMessage }}</p>
        <p v-if="saveError" class="text-sm text-red-600 mt-4">{{ saveError }}</p>

        <div class="mt-6">
          <PrimaryButton :disabled="isSaving" @click="save">{{ isSaving ? "Saving…" : "Save" }}</PrimaryButton>
        </div>
      </div>

      <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-1">Account actions</h2>
        <p class="text-sm text-gray-500 mb-4">
          Suspending signs this account out everywhere immediately. Deleting is recoverable in the database but hides
          the account from the app right away.
        </p>

        <div class="flex flex-wrap items-center gap-3">
          <SecondaryButton v-if="!user.is_suspended" :disabled="isBusy" @click="suspend">
            {{ isBusy ? "Working…" : "Suspend" }}
          </SecondaryButton>
          <SecondaryButton v-else :disabled="isBusy" @click="unsuspend">
            {{ isBusy ? "Working…" : "Unsuspend" }}
          </SecondaryButton>

          <DangerButton v-if="!user.deleted_at && user.id !== userStore.user?.id" :disabled="isBusy" @click="remove">
            {{ isBusy ? "Working…" : "Delete account" }}
          </DangerButton>
          <span v-else-if="!user.deleted_at" class="text-xs text-gray-500">You can't delete your own account.</span>
        </div>

        <p v-if="actionError" class="text-sm text-red-600 mt-4">{{ actionError }}</p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import InputLabel from "../../components/InputLabel.vue";
import TextInput from "../../components/TextInput.vue";
import Textarea from "../../components/Textarea.vue";
import PrimaryButton from "../../components/PrimaryButton.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import DangerButton from "../../components/DangerButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { useUserStore } from "../../stores/userStore.js";
import { getApiError } from "../../services/apiError.js";

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();
const userStore = useUserStore();

const user = computed(() => adminStore.currentUser);

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  job_title: "",
  industry: "",
  bio: "",
});

watch(user, (value) => {
  if (!value) {
    return;
  }
  Object.assign(form, {
    first_name: value.first_name ?? "",
    last_name: value.last_name ?? "",
    email: value.email ?? "",
    job_title: value.job_title ?? "",
    industry: value.industry ?? "",
    bio: value.bio ?? "",
  });
});

const isSaving = ref(false);
const saveMessage = ref("");
const saveError = ref("");

async function save() {
  isSaving.value = true;
  saveMessage.value = "";
  saveError.value = "";
  try {
    await adminStore.updateUser(route.params.id, { ...form });
    saveMessage.value = "Saved.";
  } catch (error) {
    saveError.value = getApiError(error, "We couldn't save those changes. Please try again.").message;
  } finally {
    isSaving.value = false;
  }
}

const isBusy = ref(false);
const actionError = ref("");

async function suspend() {
  isBusy.value = true;
  actionError.value = "";
  try {
    await adminStore.suspendUser(route.params.id);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't suspend this account. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function unsuspend() {
  isBusy.value = true;
  actionError.value = "";
  try {
    await adminStore.unsuspendUser(route.params.id);
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't unsuspend this account. Please try again.").message;
  } finally {
    isBusy.value = false;
  }
}

async function remove() {
  if (!window.confirm(`Delete ${user.value.first_name} ${user.value.last_name}'s account?`)) {
    return;
  }
  isBusy.value = true;
  actionError.value = "";
  try {
    await adminStore.deleteUser(route.params.id);
    router.push("/admin/users");
  } catch (error) {
    actionError.value = getApiError(error, "We couldn't delete this account. Please try again.").message;
    isBusy.value = false;
  }
}

onMounted(() => {
  adminStore.fetchUser(route.params.id);
});
</script>
