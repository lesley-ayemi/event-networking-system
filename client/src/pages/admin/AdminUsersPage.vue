<template>
  <AdminLayout>
    <h1 class="text-lg font-medium text-gray-900 mb-6">Users</h1>

    <div class="flex flex-wrap items-center gap-3 mb-4">
      <TextInput v-model="search" type="search" placeholder="Search by name or email" class="w-full sm:w-64" @keyup.enter="applyFilters" />
      <Select v-model="statusFilter" @change="applyFilters">
        <option value="">All statuses</option>
        <option value="suspended">Suspended</option>
        <option value="deleted">Deleted</option>
      </Select>
      <Select v-model="roleFilter" @change="applyFilters">
        <option value="">All roles</option>
        <option value="admin">Admins</option>
        <option value="organiser">Organisers</option>
      </Select>
      <SecondaryButton @click="applyFilters">Search</SecondaryButton>
    </div>

    <p v-if="adminStore.isLoadingUsers" class="text-sm text-gray-500">Loading…</p>
    <p v-else-if="adminStore.usersError" class="text-sm text-red-600">{{ adminStore.usersError }}</p>
    <p v-else-if="adminStore.users.length === 0" class="text-sm text-gray-500">No users match those filters.</p>

    <div v-else class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl divide-y divide-gray-100">
      <RouterLink
        v-for="user in adminStore.users"
        :key="user.id"
        :to="`/admin/users/${user.id}`"
        class="p-4 flex flex-wrap items-center justify-between gap-3 hover:bg-gray-50"
      >
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
          <p class="text-xs text-gray-500 break-words">{{ user.email }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
          <span v-if="user.deleted_at" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Deleted</span>
          <span v-if="user.is_suspended" class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-700">Suspended</span>
          <span v-if="user.is_admin" class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">Admin</span>
          <span v-if="user.organiser_status === 'approved'" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
            Organiser
          </span>
        </div>
      </RouterLink>
    </div>

    <div
      v-if="adminStore.usersPagination && adminStore.usersPagination.last_page > 1"
      class="flex flex-wrap items-center justify-between gap-3 mt-6"
    >
      <SecondaryButton :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Previous</SecondaryButton>
      <span class="text-sm text-gray-500">
        Page {{ adminStore.usersPagination.current_page }} of {{ adminStore.usersPagination.last_page }}
      </span>
      <SecondaryButton :disabled="currentPage === adminStore.usersPagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </SecondaryButton>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "../../layouts/AdminLayout.vue";
import TextInput from "../../components/TextInput.vue";
import Select from "../../components/Select.vue";
import SecondaryButton from "../../components/SecondaryButton.vue";
import { useAdminStore } from "../../stores/adminStore.js";

const adminStore = useAdminStore();
const search = ref("");
const statusFilter = ref("");
const roleFilter = ref("");
const currentPage = ref(1);

function load() {
  adminStore.fetchUsers({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    role: roleFilter.value || undefined,
    page: currentPage.value,
  });
}

function applyFilters() {
  currentPage.value = 1;
  load();
}

function goToPage(page) {
  currentPage.value = page;
  load();
}

onMounted(load);
</script>
