<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Admins</h1>

    <p v-if="adminStore.isLoadingAdmins" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.adminsError" class="text-sm text-red-600">{{ adminStore.adminsError }}</p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100 mb-8">
      <div v-for="admin in adminStore.admins" :key="admin.id" class="p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ admin.first_name }} {{ admin.last_name }}</p>
          <p class="text-xs text-gray-500 break-words">{{ admin.email }}</p>
        </div>
        <SecondaryButton
          v-if="admin.id !== userStore.user?.id"
          :disabled="isBusy(admin.id)"
          @click="demote(admin.id)"
        >
          Remove access
        </SecondaryButton>
        <span v-else class="text-xs text-gray-500">You</span>
      </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 max-w-md">
      <h2 class="text-base font-semibold text-gray-900 mb-4">Add an admin</h2>
      <form @submit.prevent="submitCreate" class="space-y-4">
        <div>
          <InputLabel for-id="first_name" value="First name" />
          <TextInput id="first_name" v-model="form.first_name" type="text" class="w-full" />
        </div>
        <div>
          <InputLabel for-id="last_name" value="Last name" />
          <TextInput id="last_name" v-model="form.last_name" type="text" class="w-full" />
        </div>
        <div>
          <InputLabel for-id="email" value="Email" />
          <TextInput id="email" v-model="form.email" type="email" class="w-full" />
        </div>
        <div>
          <InputLabel for-id="password" value="Password" />
          <TextInput id="password" v-model="form.password" type="password" class="w-full" />
        </div>
        <div>
          <InputLabel for-id="password_confirmation" value="Confirm password" />
          <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" />
        </div>
        <InputError :message="createError" />
        <PrimaryButton :disabled="isCreating">{{ isCreating ? "Adding…" : "Add admin" }}</PrimaryButton>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import AdminLayout from "../../layouts/AdminLayout.vue";
import InputLabel from "../../components/InputLabel.vue";
import TextInput from "../../components/TextInput.vue";
import InputError from "../../components/InputError.vue";
import PrimaryButton from "../../components/PrimaryButton.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";
import { useUserStore } from "../../stores/userStore.js";
import { getApiError } from "../../services/apiError.js";

const adminStore = useAdminStore();
const userStore = useUserStore();
const busyAdminIds = reactive(new Set());

const form = reactive({
  first_name: "",
  last_name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const isCreating = ref(false);
const createError = ref("");

function isBusy(adminId) {
  return busyAdminIds.has(adminId);
}

async function demote(adminId) {
  busyAdminIds.add(adminId);
  try {
    await adminStore.demoteAdmin(adminId);
  } catch (error) {
    adminStore.adminsError = getApiError(error, "We couldn't remove that admin's access. Please try again.").message;
  } finally {
    busyAdminIds.delete(adminId);
  }
}

async function submitCreate() {
  isCreating.value = true;
  createError.value = "";
  try {
    await adminStore.createAdmin({ ...form });
    Object.assign(form, { first_name: "", last_name: "", email: "", password: "", password_confirmation: "" });
  } catch (error) {
    createError.value = getApiError(error, "We couldn't add that admin. Please try again.").message;
  } finally {
    isCreating.value = false;
  }
}

onMounted(() => {
  adminStore.fetchAdmins();
});
</script>
