<template>
    <div class="team-roster p-4 min-h-screen" v-if="team_info.teams" :style="{ backgroundColor: '#'+team_info.teams.secondary_color, }">
        <h2 class="text-xl font-semibold text-white" v-if="team_info.teams">
            {{ team_info.teams.team_name ?? "-" }} ({{ team_info.teams.acronym ?? "-" }})
        </h2>
        <span
            v-if="team_info.teams"
            class="inline-flex items-center px-2.5 py-0.5 bg-green-300 text-green-600 rounded text-xs font-medium"
        >
            {{ team_info.teams.conference_name ?? "-" }}
        </span>
        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" />
        <!-- Modify the tabs section -->
        <div class="flex justify-between mt-5 border-b border-gray-200 p-2" 
             :style="{ backgroundColor: '#'+team_info.teams.primary_color, color: '#'+team_info.teams.secondary_color }">
            <div class="flex space-x-4">
                <!-- View Type Tabs -->
                <div class="border-r border-gray-300 pr-4 flex space-x-2">
                    <button
                        :class="['px-4 py-2 rounded-t-lg', 
                            viewType === 'roster' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="viewType = 'roster'"
                    >
                        <i class="fas fa-users mr-2"></i> Roster
                    </button>
                    <button
                        :class="['px-4 py-2 rounded-t-lg', 
                            viewType === 'depth' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="viewType = 'depth'"
                    >
                        <i class="fas fa-chart-bar mr-2"></i> Depth Chart
                    </button>
                </div>

                <!-- Filter Tabs (Only show when in roster view) -->
                <div v-if="viewType === 'roster'" class="flex space-x-2">
                    <!-- Your existing filter tabs -->
                    <button
                        :class="['px-4 py-2', currentTab === 'all' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="currentTab = 'all'"
                    >
                        <i class="fas fa-users mr-2"></i> All Players
                    </button>
                    <button
                        v-if="season_id > 1"
                        :class="['px-4 py-2', currentTab === 'new' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="currentTab = 'new'"
                    >
                        <i class="fas fa-user-plus mr-2"></i> Newly Acquired 
                        <span class="text-red-500 bg-red-200 p-1 rounded-full" v-if="season_id > 1">{{ filteredNewPlayers?.length ?? 0 }}</span>
                    </button>
                    <button
                        :class="['px-4 py-2', currentTab === 'transferred' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="currentTab = 'transferred'"
                    >
                        <i class="fas fa-exchange-alt mr-2"></i> Transferred
                        <span class="text-red-500 bg-red-200 p-1 rounded-full">{{ filteredTransferredPlayers?.length ?? 0 }}</span>
                    </button>
                    <button
                        :class="['px-4 py-2', currentTab === 'injured' ? 'border-b-2 border-blue-500 text-white' : 'hover:text-white font-bold']"
                        @click="currentTab = 'injured'"
                    >
                        <i class="fas fa-procedures mr-2"></i> Injured
                        <span class="text-red-500 bg-red-200 p-1 rounded-full">{{ filteredInjuredPlayers?.length ?? 0 }}</span>
                    </button>
                </div>
            </div>
            <div>
                <select v-model="season_id" @change="seasonBehavior()" class="mt-1 block w-full sm:w-auto border-gray-300 rounded-md shadow-sm sm:text-sm">
                    <option :key="0" value="0">Latest Roster</option>
                    <option v-for="(season, ss) in seasons" :key="season.season_id" :value="season.season_id">{{ season.name }}</option>
                </select>
            </div>
        </div>

        <!-- Content Section -->
        <div class="bg-white py-4 px-4">
            <!-- Roster View -->
            <div v-if="viewType === 'roster'">
                <div v-if="currentTab == 'all'" class="bg-white py-4 px-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-black font-bold mt-4 mb-4">All Players</h3>
                        <div>
                            <!-- Toggle Button -->
                            <button 
                                @click="showTransferred = !showTransferred"
                                class="mb-2 px-4 py-2 bg-blue-500 text-white text-xs rounded"
                            >
                                {{ showTransferred ? 'Hide Transferred Players' : 'Show Transferred Players' }}
                            </button>
                        </div>
                    </div>

                    <!-- Players Table -->
                    <div class="overflow-x-auto" >
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead :style="{ backgroundColor: '#'+team_info.teams.primary_color,color : 'white' }">
                                <tr>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        No.
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Achievements
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                    Draft
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Pos
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Exp
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. w/ Team
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. Left
                                    </th>

                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Overall Ratings"
                                    >
                                        OVR
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Best Player of the Game Count"
                                    >
                                        BPOTG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Total Team Games"
                                    >
                                        GT
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Games Played"
                                    >
                                        GP
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Minutes Per Game"
                                    >
                                        MPG
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Average Field Goal %"
                                    >
                                        AFG
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Points Per Game"
                                    >
                                        PPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Rebounds Per Game"
                                    >
                                        RPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Assist Per Game"
                                    >
                                        APG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Steals Per Game"
                                    >
                                        SPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Blocks Per Game"
                                    >
                                        BPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Turnover Per Game"
                                    >
                                        TOPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        FPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Effeciency"
                                    >
                                        EFF
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Player Effeciency Ratings"
                                    >
                                        PER
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        Ratings
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Legend
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Actions
                                    </th> -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(player, index) in filteredPlayers"
                                    :key="player.player_id"
                                    v-if="filteredPlayers?.length > 0"
                                    :class="[
                                        player.is_injured == 1 ? 'bg-red-100' : '',
                                        index >= 15 ? 'bg-gray-100' : ''
                                    ]"
                                    @click.prevent="showPlayerProfile(player)"
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border text-center">
                                        <span
                                            :title="`National Championships: ${ player.championships_won }`"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-yellow-800 bg-yellow-100 rounded-full"
                                            v-if="player.championships_won > 0">
                                            {{ player.championships_won }}
                                        </span>
                                         <span
                                            :title="`Conference Championships: ${ player.conference_championships_won }`"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full"
                                            v-if="player.conference_championships_won > 0">
                                            {{ player.conference_championships_won }}
                                        </span>
                                        <span
                                            :title="`Awards Won: ${ player.awards_won }`"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
                                            v-if="player.awards_won > 0">
                                            {{ player.awards_won }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="'Draft class: '+player.draft_class">
                                        {{ player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status + (player.drafted_team ? ' ('+player.drafted_team+ ')' : '')}}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="player.retirement_age">
                                        {{ player.name }}<sup>{{ player.age }}</sup>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.position }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.total_seasons_played }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <span
                                            :class="roleBadgeClass(player.role)"
                                           
                                        >
                                            {{ player.role }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <!-- If the player has played more than one season -->
                                        {{ player.seasons_played_with_team }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.contract_years ?? '-' }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.overall_rating ?? '-' }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.bpg_game_leader.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.team_total_games ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.games_played ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_minutes_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="`Field Goal ${player.field_goal_percentage} %`">
                                        {{ player.average_points_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_rebounds_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_assists_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_steals_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_blocks_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_turnovers_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_fouls_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <b :class="player.effeciency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.effeciency }}</b>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.per_game_score }}
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.combined_score }}
                                    </td> -->
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <span
                                            title="Newly Aquired"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
                                            v-if="player.seasons_played_with_team == 1">
                                            <i class="fas fa-user-plus text-yellow-500 mr-1"></i>
                                        </span>

                                        <!-- If the player has played more than one season -->
                                        <!-- {{ player.latest_season }}
                                        {{ player.age - (player.latest_season - player.draft_id) }} -->
                                        <span
                                            title="Less than 3 years left before retirement"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full"
                                            v-if="player.age >= player.retirement_age - 1">
                                            <i class="fas fa-user-times text-red-500 mr-1"></i>
                                        </span>
                                        <span v-if="player.status == 1" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-green-800 bg-green-100 rounded-full" title="Active">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        <span v-if="player.status == 2" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full" title="Transferred">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <span v-if="player.status == 0 && (player.latest_season  - player.draft_id != 0)" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full" title="Retired">
                                            <i class="fas fa-user-slash"></i>
                                        </span>
                                        <span v-if="player. hardship_contract > 0" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-orange-800 bg-orange-100 rounded-full" title="On a Hardship Contract">
                                            <i class="fas fa-hand-holding-medical"></i>
                                        </span>
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        <button
                                            @click="waivePlayer(player.player_id)"
                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded-l"
                                        >
                                            Waive
                                        </button>
                                        <button
                                            @click="extendContract(player.player_id)"
                                            class="px-2 py-1 bg-blue-500 text-white rounded-r text-xs"
                                        >
                                            Extend Contract
                                        </button>
                                    </td> -->
                                </tr>
                                <tr
                                    v-else
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border text-center font-bold text-red-500" colspan="24">***No Players Found***</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="currentTab == 'new'" class="bg-white py-4 px-4">
                    <h3 class="text-black font-bold mt-4 mb-4" v-if="season_id > 1">Newly Aquired Players</h3>
                    <!-- Players Table -->
                    <div class="overflow-x-auto" v-if="season_id > 1">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead :style="{ backgroundColor: '#'+team_info.teams.primary_color,color : 'white' }">
                                <tr>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        No.
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                    Draft
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Pos
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Exp
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. w/ Team
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. Left
                                    </th>

                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Overall Ratings"
                                    >
                                        OVR
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Best Player of the Game Count"
                                    >
                                        BPOTG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Total Team Games"
                                    >
                                        GT
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Games Played"
                                    >
                                        GP
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Minutes Per Game"
                                    >
                                        MPG
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Average Field Goal %"
                                    >
                                        AFG
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Points Per Game"
                                    >
                                        PPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Rebounds Per Game"
                                    >
                                        RPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Assist Per Game"
                                    >
                                        APG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Steals Per Game"
                                    >
                                        SPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Blocks Per Game"
                                    >
                                        BPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Turnover Per Game"
                                    >
                                        TOPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        FPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Effeciency"
                                    >
                                        EFF
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Player Effeciency Ratings"
                                    >
                                        PER
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        Ratings
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Legend
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Actions
                                    </th> -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(player, index) in filteredNewPlayers"
                                    :key="player.player_id"
                                    v-if="team_roster.players?.length > 0"
                                    :class="player.is_injured == 1 ? 'bg-red-100' : ''"
                                    @click.prevent="showPlayerProfile(player)"
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="'Draft class: '+player.draft_class">
                                        {{ player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status + (player.drafted_team ? ' ('+player.drafted_team+ ')' : '')}}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="player.retirement_age">
                                        {{ player.name }}<sup>{{ player.age }}</sup>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.position }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.total_seasons_played }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <span
                                            :class="roleBadgeClass(player.role)"
                                           
                                        >
                                            {{ player.role }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <!-- If the player has played more than one season -->
                                        {{ player.seasons_played_with_team }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.contract_years ?? '-' }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.overall_rating ?? '-' }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.bpg_game_leader.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.team_total_games ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.games_played ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_minutes_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="`Field Goal ${player.field_goal_percentage} %`">
                                        {{ player.average_points_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_rebounds_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_assists_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_steals_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_blocks_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_turnovers_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_fouls_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <b :class="player.effeciency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.effeciency }}</b>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.per_game_score }}
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.combined_score }}
                                    </td> -->
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <span
                                            title="Newly Aquired"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
                                            v-if="player.seasons_played_with_team == 1">
                                            <i class="fas fa-user-plus text-yellow-500 mr-1"></i>
                                        </span>
                                        <span v-if="player.status == 2" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full" title="Transferred">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <!-- If the player has played more than one season -->
                                        <!-- {{ player.latest_season }}
                                        {{ player.age - (player.latest_season - player.draft_id) }} -->
                                        <!-- <span
                                            title="Less than 3 years left before retirement"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full"
                                            v-if="player.age >= player.retirement_age - 1">
                                            <i class="fas fa-user-times text-red-500 mr-1"></i>
                                        </span>
                                        <span v-if="player.status == 1" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-green-800 bg-green-100 rounded-full" title="Active">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        <span v-if="player.status == 2" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full" title="Transferred">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <span v-if="player.status == 0 && (player.latest_season  - player.draft_id != 0)" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full" title="Retired">
                                            <i class="fas fa-user-slash"></i>
                                        </span> -->
                                        <span v-if="player. hardship_contract > 0" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-orange-800 bg-orange-100 rounded-full" title="On a Hardship Contract">
                                            <i class="fas fa-hand-holding-medical"></i>
                                        </span>
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        <button
                                            @click="waivePlayer(player.player_id)"
                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded-l"
                                        >
                                            Waive
                                        </button>
                                        <button
                                            @click="extendContract(player.player_id)"
                                            class="px-2 py-1 bg-blue-500 text-white rounded-r text-xs"
                                        >
                                            Extend Contract
                                        </button>
                                    </td> -->
                                </tr>
                                <tr
                                    v-else
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border text-center font-bold text-red-500" colspan="23">***No Players Found***</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex items-center justify-center">
                        <p>No record...</p>
                    </div>
                </div>
                <div v-if="currentTab == 'transferred'" class="bg-white py-4 px-4">
                    <h3 class="text-black font-bold mt-4 mb-4">Released Players</h3>
                    <!-- Players Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead :style="{ backgroundColor: '#'+team_info.teams.primary_color,color : 'white' }">
                                <tr>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        No.
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                    Draft
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Pos
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Exp
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. w/ Team
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. Left
                                    </th>

                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Overall Ratings"
                                    >
                                        OVR
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Best Player of the Game Count"
                                    >
                                        BPOTG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Total Team Games"
                                    >
                                        GT
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Games Played"
                                    >
                                        GP
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Minutes Per Game"
                                    >
                                        MPG
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Average Field Goal %"
                                    >
                                        AFG
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Points Per Game"
                                    >
                                        PPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Rebounds Per Game"
                                    >
                                        RPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Assist Per Game"
                                    >
                                        APG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Steals Per Game"
                                    >
                                        SPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Blocks Per Game"
                                    >
                                        BPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Turnover Per Game"
                                    >
                                        TOPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        FPG
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Effeciency"
                                    >
                                        EFF
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Player Effeciency Ratings"
                                    >
                                        PER
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Fouls Per Game"
                                    >
                                        Ratings
                                    </th> -->
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Legend
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Actions
                                    </th> -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(player, index) in filteredTransferredPlayers"
                                    :key="player.player_id"
                                    v-if="filteredTransferredPlayers?.length > 0"
                                    :class="player.is_injured == 1 ? 'bg-red-100' : ''"
                                    @click.prevent="showPlayerProfile(player)"
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="'Draft class: '+player.draft_class">
                                        {{ player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status + (player.drafted_team ? ' ('+player.drafted_team+ ')' : '')}}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="player.retirement_age">
                                        {{ player.name }}<sup>{{ player.age }}</sup>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.position }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.total_seasons_played }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <span
                                            :class="roleBadgeClass(player.role)"
                                           
                                        >
                                            {{ player.role }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <!-- If the player has played more than one season -->
                                        {{ player.seasons_played_with_team }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.contract_years ?? '-' }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.overall_rating ?? '-' }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.bpg_game_leader.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.team_total_games ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ Math.round(player.games_played ?? 0) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_minutes_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="`Field Goal ${player.field_goal_percentage} %`">
                                        {{ player.average_points_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_rebounds_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_assists_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_steals_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_blocks_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_turnovers_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.average_fouls_per_game.toFixed(1) }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <b :class="player.effeciency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.effeciency }}</b>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.per_game_score }}
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.combined_score }}
                                    </td> -->
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <!-- <span
                                            title="Newly Aquired"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
                                            v-if="player.seasons_played_with_team == 1">
                                            <i class="fas fa-user-plus text-yellow-500 mr-1"></i>
                                        </span> -->

                                        <!-- If the player has played more than one season -->
                                        <!-- {{ player.latest_season }}
                                        {{ player.age - (player.latest_season - player.draft_id) }} -->
                                        <!-- <span
                                            title="Less than 3 years left before retirement"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full"
                                            v-if="player.age >= player.retirement_age - 1">
                                            <i class="fas fa-user-times text-red-500 mr-1"></i>
                                        </span>
                                        <span v-if="player.status == 1" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-green-800 bg-green-100 rounded-full" title="Active">
                                            <i class="fas fa-check-circle"></i>
                                        </span> -->
                                        <span v-if="player.status == 2" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full" title="Transferred">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <!-- <span v-if="player.status == 0 && (player.latest_season  - player.draft_id != 0)" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full" title="Retired">
                                            <i class="fas fa-user-slash"></i>
                                        </span> -->
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        <button
                                            @click="waivePlayer(player.player_id)"
                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded-l"
                                        >
                                            Waive
                                        </button>
                                        <button
                                            @click="extendContract(player.player_id)"
                                            class="px-2 py-1 bg-blue-500 text-white rounded-r text-xs"
                                        >
                                            Extend Contract
                                        </button>
                                    </td> -->
                                </tr>
                                <tr
                                    v-else
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border text-center font-bold text-red-500" colspan="23">***No Players Found***</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="currentTab == 'injured'" class="bg-white py-4 px-4">
                    <h3 class="text-black font-bold mt-4 mb-4">Injured Players</h3>
                    <!-- Players Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead :style="{ backgroundColor: '#'+team_info.teams.primary_color,color : 'white' }">
                                <tr>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        No.
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                    Draft
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Pos
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Exp
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. w/ Team
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  text-wrap uppercase tracking-wider"
                                        title="Remaining Contract Years"
                                    >
                                        Yrs. Left
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Estimated time of absence"
                                    >
                                        Injury Name
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                        title="Estimated time of absence"
                                    >
                                        ETA
                                    </th>
                                    <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Legend
                                    </th>
                                    <!-- <th
                                        class="px-2 py-1 text-left font-medium  uppercase tracking-wider"
                                    >
                                        Actions
                                    </th> -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="(player, index) in filteredInjuredPlayers"
                                    :key="player.player_id"
                                    v-if="filteredInjuredPlayers?.length > 0"
                                    :class="player.is_injured == 1 ? 'bg-red-100' : ''"
                                    @click.prevent="showPlayerProfile(player)"
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="'Draft class: '+player.draft_class">
                                        {{ player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status + (player.drafted_team ? ' ('+player.drafted_team+ ')' : '')}}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border" :title="player.retirement_age">
                                        {{ player.name }}<sup>{{ player.age }}</sup>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.position }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.total_seasons_played }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <span
                                            :class="roleBadgeClass(player.role)"
                                           
                                        >
                                            {{ player.role }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <!-- If the player has played more than one season -->
                                        {{ player.seasons_played_with_team }} yrs.
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.contract_years ?? '-' }} yrs.
                                    </td>
                                     <td class="px-2 py-1 whitespace-nowrap border first-letter:uppercase">
                                        {{ player.injury_type?.replaceAll('_',' ') ?? '-' }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                       {{ Math.round(player.injury_recovery_games) ?? '-' }} {{ Math.round(player.injury_recovery_games) === 1 ? 'Day' : 'Days' }}
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- If the player is new to the team -->
                                        <span
                                            title="Newly Aquired"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
                                            v-if="player.seasons_played_with_team == 1">
                                            <i class="fas fa-user-plus text-yellow-500 mr-1"></i>
                                        </span>

                                        <!-- If the player has played more than one season -->
                                        <!-- {{ player.latest_season }}
                                        {{ player.age - (player.latest_season - player.draft_id) }} -->
                                        <span
                                            title="Less than 3 years left before retirement"
                                            class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full"
                                            v-if="player.age >= player.retirement_age - 1">
                                            <i class="fas fa-user-times text-red-500 mr-1"></i>
                                        </span>
                                        <span v-if="player.status == 1" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-green-800 bg-green-100 rounded-full" title="Active">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        <span v-if="player.status == 2" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full" title="Transferred">
                                            <i class="fas fa-exchange-alt"></i>
                                        </span>
                                        <span v-if="player.status == 0 && (player.latest_season  - player.draft_id != 0)" class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full" title="Retired">
                                            <i class="fas fa-user-slash"></i>
                                        </span>
                                    </td>
                                    <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                        <button
                                            @click="waivePlayer(player.player_id)"
                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded-l"
                                        >
                                            Waive
                                        </button>
                                        <button
                                            @click="extendContract(player.player_id)"
                                            class="px-2 py-1 bg-blue-500 text-white rounded-r text-xs"
                                        >
                                            Extend Contract
                                        </button>
                                    </td> -->
                                </tr>
                                <tr
                                    v-else
                                    class="hover:bg-gray-100"
                                >
                                    <td class="px-2 py-1 whitespace-nowrap border text-center font-bold text-red-500" colspan="23">***No Players Found***</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Depth Chart View -->
            <div v-if="viewType === 'depth'" class="bg-white py-4 px-4">
                <h3 class="text-black font-bold mt-4 mb-4">Depth Chart</h3>
                <!-- Depth Chart Content -->
                <DepthChartRow v-if="team_roster" :key="team_roster?.team_id" :players="team_roster?.players" :season_id="season_id"/>
            </div>
        </div>
        <!-- Modal for Player Profile -->
        <Modal :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Profile" @close="showPlayerProfileModal = false">
            <div class="p-6 block">
                <!-- Image Section -->
                <PlayerPerformance :key="selectedPlayer.player_id" :player_id="selectedPlayer.player_id" />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import axios from "axios";
import { roleBadgeClass } from "@/Utility/Formatter";

import PlayerPerformance from "../../Players/Module/PlayerPerformance.vue";
import DepthChartRow from "./DepthChartRow.vue";
const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});
const showExtendModal = ref(false);
const showTransferred = ref(true);
const currentTab = ref('all');
const viewType = ref('roster');
const showPlayerProfileModal = ref(false);
const selectedPlayer = ref(null);
const additionalYears = ref(1);
const newPlayerName = ref("");
const team_roster = ref([]);
const team_info = ref([]);
const seasons = ref([]);
const season_id = ref(0);
watch(
    () => props.team_id,
    async (newId, oldId) => {
        if (newId !== oldId) {
            await fetchTeamInfo(newId);
        }
    }
);

onMounted(async () => {
    await seasonsDropdown();
    await fetchTeamInfo(props.team_id);
    await fetchTeamRoster(props.team_id);
});
const toggleShowTransferred = () => {
    showTransferred.value = !showTransferred.value;
};

const filteredPlayers = computed(() => {
    return team_roster.value.players?.filter(player => 
        showTransferred.value || player.status !== 2
    );
});

const filteredTransferredPlayers = computed(() => {
    return team_roster.value.players?.filter(player => 
        player.status == 2
    );
});

const filteredNewPlayers = computed(() => {
    return team_roster.value.players?.filter(player => 
        player.seasons_played_with_team == 1
    );
});

const filteredInjuredPlayers = computed(() => {
    return team_roster.value.players?.filter(player => 
        player.is_injured == 1 && player.status != 2
    );
});

const sortByMinutes = (players) => {
    return [...players].sort((a, b) => b.average_minutes_per_game - a.average_minutes_per_game);
};

const guardDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value?.filter(p => 
            p.position.includes('G') && p.status !== 2
        ) || []
    );
});

const forwardDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value?.filter(p => 
            p.position.includes('F') && p.status !== 2
        ) || []
    );
});

const centerDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value?.filter(p => 
            p.position.includes('C') && p.status !== 2
        ) || []
    );
});

const fetchTeamInfo = async (id) => {
    try {
        const response = await axios.post(route("teams.info"), {
            team_id: id,
        });
        team_info.value = response.data;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};

const fetchTeamRoster = async (id) => {
    try {
        team_roster.value = [];
        const response = await axios.post(route("players.team.roster"), {
            team_id: id,
            season_id: season_id.value,
        });
        team_roster.value = response.data;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const seasonsDropdown = async () => {
    try {
        const response = await axios.post(route("seasons.dropdown"), {
            season_id: 0,
        });
        seasons.value = response.data;
        console.log(response.data[0].season_id);
        season_id.value = response.data[0].season_id ?? 0;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const seasonBehavior = () => {
    fetchTeamRoster(props.team_id); // Refresh team info
}
const addPlayer = async () => {
    try {
        const response = await axios.post(route("players.add"), {
            name: newPlayerName.value,
            team_id: props.team_id,
        });
        newPlayerName.value = ""; // Clear the input
        // showAddPlayerModal.value = false;
        // Swal.fire({
        //     icon: "success",
        //     title: "Success!",
        //     text: response.data.message, // Assuming the response contains a 'message' field
        // });
        fetchTeamRoster(props.team_id); // Refresh team info
    } catch (error) {
        console.error("Error adding player:", error);
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response.data.message, // Assuming the response contains a 'message' field
        });
    }
};

const waivePlayer = async (playerId) => {
    try {
        await axios.post(route("players.waive"), { id: playerId });
        fetchTeamRoster(props.team_id); // Refresh team info
    } catch (error) {
        console.error("Error waiving player:", error);
    }
};

const extendContract = (playerId) => {
    selectedPlayer.value = team_info.value.players.find(
        (player) => player.id === playerId
    );
    showExtendModal.value = true;
};

const confirmExtendContract = async () => {
    try {
        await axios.post(route("players.contract.extend"), {
            id: selectedPlayer.value.id,
            additional_years: additionalYears.value,
        });
        showExtendModal.value = false;
        fetchTeamRoster(props.team_id); // Refresh team info
    } catch (error) {
        console.error("Error extending contract:", error);
    }
};

const showPlayerProfile = (player) => {
    selectedPlayer.value = player;
    showPlayerProfileModal.value = true;
};


const playerStatusClass = (isActive) => {
    return isActive ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800";
};

const playerStatusText = (isActive) => {
    return isActive ? "Active" : "Waived";
};
</script>

<style scoped>
.table {
    font-size: 0.75rem; /* Smaller text size */
}

.table th,
.table td {
    padding: 0.5rem; /* Smaller padding */
}
</style>
