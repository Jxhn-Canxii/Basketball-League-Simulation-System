<template>
    <div>
        <Head title="Users" />

        <AuthenticatedLayout>
            <template #header> Users </template>
            <div class="inline-block min-w-full bg-white overflow-hidden rounded shadow p-2">
                <Add @transaction_id="handleTransaction()" />
                <input
                    type="text"
                    v-model="search.search"
                    @input.prevent="fetchUsers()"
                    id="LeagueName"
                    placeholder="Enter User"
                    class="mt-1 mb-2 p-2 border rounded w-full"
                />
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Name
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Email
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in data.users" v-if="data.total_pages" :key="user.id" class="text-gray-700">
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    {{ user.name }}
                                </p>
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    {{ user.email }}
                                </p>
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <div class="flex">
                                    <Edit :key="user.id" :data="user" @transaction_id="handleTransaction()" />
                                    <Delete :key="user.id" :user_id="user.id" @transaction_id="handleTransaction()" />
                                </div>
                            </td>
                        </tr>
                        <tr v-else>
                            <td colspan="3" class="border-b text-center font-bold text-lg border-gray-200 bg-white px-5 py-5">
                                <p class="text-red-500 whitespace-no-wrap">
                                    No Data Found!
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex w-full overflow-auto">
                    <Paginator
                        v-if="data.total_count"
                        :page_number="search.page_num"
                        :total_rows="data.total_count ?? 0"
                        :itemsperpage="search.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import Paginator from "@/Components/Paginator.vue";
import { ref, onMounted } from "vue";
import axios from "axios";
import Add from "./Module/Add.vue";
import Edit from "./Module/Edit.vue";
import Delete from "./Module/Delete.vue";

const data = ref([]);

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});
onMounted(() => {
    fetchUsers();
});

const fetchUsers = async () => {
    try {
        const response = await axios.post(route("users.list"), search.value);
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching users:", error);
    }
};
const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    fetchUsers();
};

const handleTransaction = () => {
    fetchUsers();
}
</script>
