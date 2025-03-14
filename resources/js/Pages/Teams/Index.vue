<template>
    <div>
        <Head title="Teams" />

        <AuthenticatedLayout>
            <template #header> Teams </template>
            <div class="inline-block min-w-full bg-white overflow-hidden rounded shadow p-2">
                <Add @transaction_id="handleTransaction()" />
                <input
                    type="text"
                    v-model="search_teams.search"
                    @input.prevent="fetchTeams()"
                    id="LeagueName"
                    placeholder="Enter team name"
                    class="mt-1 mb-2 p-2 border rounded w-full"
                />
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Team Name
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                League
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Conference
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Colors
                            </th>
                            <th class="border-b-2 border-gray-200 bg-gray-100 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="team in teams.teams" v-if="teams.total_pages" :key="team.id" class="text-gray-700">
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <TeamDetails
                                :team_id="team.id" 
                                :key="team.id"
                                :showInfo="false"
                                :current_conference_rank="0"
                                :season_id="teams.current_season"
                                :showButton="0" 
                                :text="`${team.name} (${team.acronym})`" />
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    {{ team.league_name }}
                                </p>
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    {{ team.conference_name }} Conference
                                </p>
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full border" :style="{ backgroundColor: '#'+team.primary_color }"></span>
                                    <span class="w-5 h-5 rounded-full border" :style="{ backgroundColor: '#'+team.secondary_color }"></span>
                                </div>
                            </td>
                            <td class="border-b border-gray-200 bg-white px-5 py-5 text-sm">
                                <div class="flex">
                                    <TeamDetails :team_id="team.id" :key="team.id" :showButton="1" text="View" />
                                    <Edit :key="team.id" :data="team" @transaction_id="handleTransaction()" />
                                    <Delete :key="team.id" :team_id="team.id" @transaction_id="handleTransaction()" />
                                </div>
                            </td>
                        </tr>
                        <tr v-else>
                            <td colspan="5" class="border-b text-center font-bold text-lg border-gray-200 bg-white px-5 py-5">
                                <p class="text-red-500 whitespace-no-wrap">
                                    No Data Found!
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex w-full overflow-auto">
                    <Paginator
                        v-if="teams.total_count"
                        :page_number="search_teams.page_num"
                        :total_rows="teams.total_count ?? 0"
                        :itemsperpage="search_teams.itemsperpage"
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
import TeamDetails from "./Module/TeamDetails.vue";
import Add from "./Module/Add.vue";
import Edit from "./Module/Edit.vue";
import Delete from "./Module/Delete.vue";

const teams = ref([]);

const search_teams = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});
onMounted(() => {
    fetchTeams();
});

const fetchTeams = async () => {
    try {
        const response = await axios.post(route("teams.list"), search_teams.value);
        teams.value = response.data;
    } catch (error) {
        console.error("Error fetching teams:", error);
    }
};
const handlePagination = (page_num) => {
    search_teams.value.page_num = page_num ?? 1;
    fetchTeams();
};

const handleTransaction = () => {
    fetchTeams();
}
</script>
