<template>
    <div class="p-4 bg-gray-900 shadow-md min-h-screen flex justify-center items-center rounded-lg max-w-7xl mx-auto" v-if="!gameDetails">
        <!-- Skeleton Loader -->
        <div class="flex justify-center items-center h-full">
            <!-- Centered Loader with Timer -->
            <div class="flex flex-col items-center space-y-6">
                <!-- Timer Display -->
                <div class="text-white text-xl text-center font-mono mb-4">
                    <h2>Liga Pilipinas</h2>
                    <small>#{{ props.game_id }}</small>
                </div> 

                <!-- Existing skeleton loader content -->
                <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse"></div>
                <div class="w-24 h-8 bg-gray-700 rounded-md animate-pulse"></div>
                <div class="text-white text-md font-semibold">
                   <span class="animate-pulse font-mono">Loading Game Data: {{ formatTime(time) }}</span>
                </div>
                <div class="w-24 h-8 bg-gray-700 rounded-md animate-pulse"></div>
                <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse"></div>
                <div class="w-48 h-6 bg-gray-700 rounded-md animate-pulse mt-4"></div>
                <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse mt-4"></div>
            </div>
        </div>
    </div>
    <div class="p-4 bg-gray-900 shadow-md rounded-lg max-w-7xl mx-auto" v-else>
        <!-- Game Summary -->
        <div
            class="flex flex-col lg:flex-row justify-between mb-4 border-b-2 border-gray-700 pb-4"
        >
            <div
                class="flex-1 text-center mb-2 lg:mb-0 team-card rounded relative order-1 md:order-1"
                :style="{
                    backgroundColor: '#' + gameDetails?.home_team.primary_color,
                }"
            >
                <!-- Home team primary color -->
                <h2 class="text-5xl font-bold text-white">
                    {{ gameDetails?.home_team.score }}
                </h2>
                <p
                    class="text-md font-semibold text-white text-center"
                    :style="{
                        backgroundColor:
                            '#' + gameDetails?.home_team.secondary_color,
                    }"
                >
                    <TeamDetails :team_id="gameDetails?.home_team.team_id" :key="gameDetails?.home_team.team_id" :showButton="0" :text="`${gameDetails?.home_team.city} ${gameDetails?.home_team.name} (${ gameDetails?.home_team.streak })`" />
                </p>
                <div class="flex justify-center" v-if="!props.showBoxScore">
                    <ul class="flex space-x-2 mt-2">
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-7 h-7 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-sm font-bold text-white">{{
                                    gameDetails?.home_team.ratings.offense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">OFF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-red-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.home_team.ratings.defense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">DEF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-violet-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.home_team.ratings.passing_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">PASS</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-yellow-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.home_team.ratings.rebounding_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">REB</p>
                        </li>
                    </ul>
                </div>
                <small class="absolute top-0 right-0 font-bold text-gray-200"># {{ gameDetails?.home_team.team_id }}</small>
            </div>

            <div class="flex-1 text-center mb-2 lg:mb-0 text-white order-3 md:order-2">
                <div class="bg-gray-800 p-2 rounded-lg m-1">
                    <p class="text-xs font-semibold text-yellow-500">
                        Liga Dos
                        {{
                            isNaN(gameDetails?.round)
                                ? "Playoffs"
                                : "Regular Season"
                        }}
                    </p>
                    <p class="text-xs font-semibold">
                        Round:
                        {{
                            roundNameFormatter(
                                isNaN(gameDetails?.round)
                                    ? gameDetails?.round
                                    : parseFloat(gameDetails?.round)
                            )
                        }}
                    </p>
                    <p class="text-xs font-semibold">
                        Game ID: {{ gameDetails?.game_id }}
                    </p>
                    <p class="text-xs font-semibold">
                        Matchup Record:
                        {{
                            gameDetails?.head_to_head_record.home_team_wins ?? 0
                        }}
                        -
                        {{
                            gameDetails?.head_to_head_record.away_team_wins ?? 0
                        }}
                    </p>
                    <!-- <div class="timer">
                        <p class="text-xs text-gray-300">{{ formatTime(time) }} seconds</p>
                    </div> -->
                </div>
                <div class="flex flex-col p-2 m-1 bg-slate-800 rounded text-white"  v-if="!props.showBoxScore && seasonLeaders">
                    <small class="text-xs text-nowrap text-gray-500">{{ seasonLeaders.message }}</small>
                    <small class="text-xs text-nowrap font-bold" :title="seasonLeaders.draft_status">{{ seasonLeaders.player_name }} ({{ seasonLeaders.stat_value }} {{ seasonLeaders.stat_type }})</small>
                    <small class="text-xs text-nowrap text-gray-500">{{ seasonLeaders.team_name }}</small>
                </div>
            </div>

            <div
                class="flex-1 text-center mb-2 lg:mb-0 team-card rounded relative order-2 md:order-3"
                :style="{
                    backgroundColor: '#' + gameDetails?.away_team.primary_color,
                }"
            >
                <!-- Away team primary color -->
                <h2 class="text-5xl font-bold text-white">
                    {{ gameDetails?.away_team.score }}
                </h2>
                <p
                    class="text-md font-semibold text-white text-center"
                    :style="{
                        backgroundColor:
                            '#' + gameDetails?.away_team.secondary_color,
                    }"
                >
                    <TeamDetails :team_id="gameDetails?.away_team.team_id" :key="gameDetails?.away_team.team_id" :showButton="0" :text="`${gameDetails?.away_team.city} ${gameDetails?.away_team.name} (${ gameDetails?.away_team.streak })`" />
                </p>
                <div class="flex justify-center" v-if="!props.showBoxScore">
                    <ul class="flex space-x-2 mt-2">
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-7 h-7 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.offense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">OFF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-red-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.defense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">DEF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-violet-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.passing_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">PASS</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-7 h-7 p-2 bg-yellow-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.rebounding_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">REB</p>
                        </li>
                    </ul>
                </div>
                <small class="absolute top-0 right-0 font-bold text-gray-200"># {{ gameDetails?.away_team.team_id }}</small>
            </div>
        </div>

        <!-- Player Statistics Tables -->
        <div class="mb-4 text-white" v-if="props.showBoxScore">
            <h3 class="text-xl font-semibold mb-2">Player Statistics</h3>

            <!-- Home Team Player Stats -->
            <div
                class="mb-2 p-2 rounded"
                :style="{
                    backgroundColor: '#' + gameDetails?.home_team.primary_color,
                }"
            >
            <div class="flex justify-between">
                <h4 class="text-lg font-semibold flex items-center mb-1">
                    {{ gameDetails?.home_team.name }} Player Stats
                </h4>
                <ul class="flex space-x-2">
                    <li class="flex flex-col items-center">
                        <span
                            class="flex-shrink-0 w-10 h-10 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                gameDetails?.home_team.ratings.offense_rating
                            }}</span>
                        </span>
                        <p class="text-xs text-gray-900 font-bold">OFF</p>
                    </li>
                    <li class="flex flex-col items-center">
                        <span
                        class="flex-shrink-0 w-10 h-10 p-2 bg-red-600 rounded-full flex items-center justify-center"
                    >
                        <span class="text-sm font-bold text-white">{{
                                gameDetails?.home_team.ratings.defense_rating
                            }}</span>
                        </span>
                        <p class="text-xs text-gray-900 font-bold">DEF</p>
                    </li>
                    <li class="flex flex-col items-center">
                        <span
                        class="flex-shrink-0 w-10 h-10 p-2 bg-violet-600 rounded-full flex items-center justify-center"
                    >
                        <span class="text-sm font-bold text-white">{{
                                gameDetails?.home_team.ratings.passing_rating
                            }}</span>
                        </span>
                        <p class="text-xs text-gray-900 font-bold">PASS</p>
                    </li>
                    <li class="flex flex-col items-center">
                        <span
                        class="flex-shrink-0 w-10 h-10 p-2 bg-yellow-600 rounded-full flex items-center justify-center"
                    >
                        <span class="text-sm font-bold text-white">{{
                                gameDetails?.home_team.ratings.rebounding_rating
                            }}</span>
                        </span>
                        <p class="text-xs text-gray-900 font-bold">REB</p>
                    </li>
                </ul>
            </div>
                <table
                    class="min-w-full bg-gray-800 rounded-lg overflow-hidden text-sm"
                >
                    <thead>
                        <tr
                            class="bg-gray-700 text-left"
                            :style="{
                                backgroundColor:
                                    '#' +
                                    gameDetails?.home_team.secondary_color,
                            }"
                        >
                            <th class="py-2 px-3 text-xs">Name</th>
                            <th class="py-2 px-3 text-xs">Role</th>
                            <th class="px-2 py-3 text-xs text-right">Min</th>
                            <th class="px-2 py-3 text-xs text-right" title="Points Made">Pts</th>
                            <th class="px-2 py-3 text-xs text-right" title="Rebounds Made">Reb</th>
                            <th class="px-2 py-3 text-xs text-right" title="Assist Made">Ast</th>
                            <th class="px-2 py-3 text-xs text-right" title="Steals Made">Stl</th>
                            <th class="px-2 py-3 text-xs text-right" title="Blocks Made">Blk</th>
                            <th class="px-2 py-3 text-xs text-right" title="Turnover Made">TO</th>
                            <th class="px-2 py-3 text-xs text-right" title="Fouls Made">Fouls</th>
                            <!-- <th class="px-2 py-3 text-xs" title="Field Goals Made / Attempted">Field Goals</th>
                            <th class="px-2 py-3 text-xs" title="2PT Made / Attempted">2PT</th>
                            <th class="px-2 py-3 text-xs" title="3PT Made / Attempted">3PT</th>
                            <th class="px-2 py-3 text-xs" title="Free Throws Made / Attempted">Free Throws</th> -->
                            <th class="px-2 py-3 text-xs text-right" title="Player Efficiency Rating">PER</th>
                            <th class="px-2 py-3 text-xs text-right" title="Efficiency">EFF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="player in sortedHomePlayers"
                            :key="player.name"
                            @click.prevent="showPlayerProfileModal = player"
                            :class="{
                                'bg-yellow-100 text-black':
                                    top5HomePlayers.includes(player.name),
                            }"
                            class="border-b hover:bg-gray-600 text-xs"
                        >
                            <td class="py-1 px-3">
                                {{ player.name
                                }}<sup>{{ player.is_rookie ? "R" : "V" }}</sup>
                            </td>
                            <td class="py-1 px-3">
                                <span :class="roleBadgeClass(player.role)">{{
                                    player.role
                                }}</span>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? 'DNP' : player.minutes.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.points.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.rebounds.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.assists.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.steals.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.blocks.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? (0).toFixed(1) : player.turnovers.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? (0).toFixed(1) : player.fouls.toFixed(1) }}</td>
                            <!-- Field Goal Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.field_goals_made }} / {{ player.field_goal_attempts }} ({{ player.field_goal_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- 3PT Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.two_pointers_made }} / {{ player.two_point_attempts }} ({{ player.two_point_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- 3PT Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.three_pointers_made }} / {{ player.three_point_attempts }} ({{ player.three_point_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- Free Throw Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.free_throws_made }} / {{ player.free_throw_attempts }} ({{ player.free_throw_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- PER -->
                            <td class="px-2 py-1 whitespace-nowrap border text-right">
                                {{ isNaN(parseFloat(player.per)) ? 0 : parseFloat(player.per).toFixed(2) }}
                            </td>
                            <!-- EFF -->
                            <td class="px-2 py-1 whitespace-nowrap border text-right"><b :class="player.efficiency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.efficiency }}</b></td>
                        </tr>
                        <tr v-if="sortedHomePlayers.length === 0">
                            <td
                                colspan="10"
                                class="py-1 px-3 text-center text-xs"
                            >
                                No player statistics available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Away Team Player Stats -->
            <div
                class="mb-2 p-2 rounded"
                :style="{
                    backgroundColor: '#' + gameDetails?.away_team.primary_color,
                }"
            >
                <div class="flex justify-between">
                    <h4 class="text-lg font-semibold flex items-center mb-1">
                        {{ gameDetails?.away_team.name }} Player Stats
                    </h4>
                    <ul class="flex space-x-2">
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-10 h-10 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.offense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">OFF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-10 h-10 p-2 bg-red-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.defense_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">DEF</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-10 h-10 p-2 bg-violet-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.passing_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">PASS</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                            class="flex-shrink-0 w-10 h-10 p-2 bg-yellow-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-sm font-bold text-white">{{
                                    gameDetails?.away_team.ratings.rebounding_rating
                                }}</span>
                            </span>
                            <p class="text-xs text-gray-900 font-bold">REB</p>
                        </li>
                    </ul>
                </div>

                <table
                    class="min-w-full bg-gray-800 rounded-lg overflow-hidden text-sm"
                >
                    <thead>
                        <tr
                            class="bg-gray-700 text-left"
                            :style="{
                                backgroundColor:
                                    '#' +
                                    gameDetails?.away_team.secondary_color,
                            }"
                        >
                            <th class="py-2 px-3 text-xs">Name</th>
                            <th class="py-2 px-3 text-xs">Role</th>
                            <th class="px-2 py-3 text-xs text-right">Min</th>
                            <th class="px-2 py-3 text-xs text-right" title="Points Made">Pts</th>
                            <th class="px-2 py-3 text-xs text-right" title="Rebounds Made">Reb</th>
                            <th class="px-2 py-3 text-xs text-right" title="Assist Made">Ast</th>
                            <th class="px-2 py-3 text-xs text-right" title="Steals Made">Stl</th>
                            <th class="px-2 py-3 text-xs text-right" title="Blocks Made">Blk</th>
                            <th class="px-2 py-3 text-xs text-right" title="Turnover Made">TO</th>
                            <th class="px-2 py-3 text-xs text-right" title="Fouls Made">Fouls</th>
                            <!-- <th class="px-2 py-3 text-xs" title="Field Goals Made / Attempted">Field Goals</th>
                            <th class="px-2 py-3 text-xs" title="2PT Made / Attempted">2PT</th>
                            <th class="px-2 py-3 text-xs" title="3PT Made / Attempted">3PT</th>
                            <th class="px-2 py-3 text-xs" title="Free Throws Made / Attempted">Free Throws</th> -->
                            <th class="px-2 py-3 text-xs text-right" title="Player Efficiency Rating">PER</th>
                            <th class="px-2 py-3 text-xs text-right" title="Efficiency">EFF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="player in sortedAwayPlayers"
                            :key="player.name"
                            @click.prevent="showPlayerProfileModal = player"
                            :class="{
                                'bg-yellow-100 text-black':
                                    top5AwayPlayers.includes(player.name),
                            }"
                            class="border-b hover:bg-gray-600 text-xs"
                        >
                            <td class="py-1 px-3">
                                {{ player.name
                                }}<sup>{{ player.is_rookie ? "R" : "V" }}</sup>
                            </td>
                            <td class="py-1 px-3">
                                <span :class="roleBadgeClass(player.role)">{{
                                    player.role
                                }}</span>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? 'DNP' : player.minutes.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.points.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.rebounds.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.assists.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.steals.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.blocks.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? (0).toFixed(1) : player.turnovers.toFixed(1) }}</td>
                            <td class="px-2 py-1 whitespace-nowrap border text-right">{{ player.minutes === 0 ? (0).toFixed(1) : player.fouls.toFixed(1) }}</td>
                            <!-- Field Goal Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.field_goals_made }} / {{ player.field_goal_attempts }} ({{ player.field_goal_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- 3PT Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.two_pointers_made }} / {{ player.two_point_attempts }} ({{ player.two_point_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- 3PT Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.three_pointers_made }} / {{ player.three_point_attempts }} ({{ player.three_point_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- Free Throw Stats -->
                            <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                {{ player.free_throws_made }} / {{ player.free_throw_attempts }} ({{ player.free_throw_percentage.toFixed(1) }}%)
                            </td> -->
                            <!-- PER -->
                            <td class="px-2 py-1 whitespace-nowrap border text-right">
                                {{ isNaN(parseFloat(player.per)) ? 0 : parseFloat(player.per).toFixed(2) }}
                            </td>
                            <!-- EFF -->
                            <td class="px-2 py-1 whitespace-nowrap border text-right"><b :class="player.efficiency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.efficiency ?? 0 }}</b></td>
                        </tr>
                        <tr v-if="sortedHomePlayers.length === 0">
                            <td
                                colspan="10"
                                class="py-1 px-3 text-center text-xs"
                            >
                                No player statistics available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Best Player of the Game -->
        <div class="block md:flex bg-white rounded">
            <!-- Best Player Section: 1/4 Width -->
            <div class="w-full md:w-1/2 p-2">
                <h3 class="text-lg font-semibold mb-1">Player of the Game</h3>
                <div
                    v-if="bestPlayer"
                    class="bg-white shadow-lg p-4 rounded-lg text-black"
                >
                    <div class="flex flex-col items-center text-white mx-0 p-0 rounded"
                    :style="{
                        backgroundColor:
                            '#' + (gameDetails?.home_team.score > gameDetails?.away_team.score ? gameDetails?.home_team.primary_color : gameDetails?.away_team.primary_color),
                    }"
                    >
                        <p class="text-4xl font-extrabold mb-1 relative" :title="bestPlayer?.name">
                            {{ playerFormatter(bestPlayer?.name) }}
                            <sup class="text-xs absolute top-0" v-if="bestPlayer?.age">
                               {{ bestPlayer?.age }}
                            </sup>
                        </p>
                        <div class="flex justify-center p-2">
                            <span
                                :class="roleBadgeClass(bestPlayer.role)"
                            >
                                {{ bestPlayer.role }}
                            </span>
                        </div>
                        <div class="flex w-full justify-center px-0 mx-0"
                        :style="{
                            backgroundColor:
                                '#' + (gameDetails?.home_team.score > gameDetails?.away_team.score ? gameDetails?.home_team.secondary_color : gameDetails?.away_team.secondary_color),
                        }"
                        >
                            <p class="text-xl">
                                {{ bestPlayer?.team }}
                            </p>
                        </div>
                    </div>
                    <ul class="grid grid-cols-3 gap-4 p-4">
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.points
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">PTS</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.rebounds
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">REB</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.assists
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">AST</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.steals
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">STL</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.blocks
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">BLK</p>
                        </li>
                        <li class="flex flex-col items-center">
                            <span
                                class="flex-shrink-0 w-25 h-25 p-2 bg-red-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-6xl font-bold text-white">{{
                                    bestPlayer?.turnovers
                                }}</span>
                            </span>
                            <p class="text-xl text-gray-900 font-bold">TO</p>
                        </li>
                    </ul>

                    <!-- Marquee for awards -->
                    <div class="mt-4 block justify-start text-wrap">
                        <p class="text-xs font-bold text-gray-600" v-if="bestPlayer?.awards && bestPlayer?.awards.length > 0">
                            {{ bestPlayer?.awards }}
                        </p>
                        <p class="text-xs font-bold text-gray-600" v-if="bestPlayer?.finals_mvp && bestPlayer?.finals_mvp.length > 0">
                             {{ bestPlayer?.finals_mvp }}
                        </p>
                        <p class="text-xs font-bold text-gray-600" v-if="bestPlayer?.championship_won && bestPlayer?.championship_won.length > 0">
                            {{ bestPlayer?.championship_won }}
                       </p>
                    </div>
                    <div class="flex justify-between text-nowrap items-center">
                        <sup class="float-center font-bold mt-0 text-red-500">
                            <b class="text-gray-400">Draft:</b> {{ bestPlayer.draft_status == 'Undrafted' ? 'S'+bestPlayer.draft_id+' '+bestPlayer.draft_status : bestPlayer.draft_status + (bestPlayer.drafted_team_acro ? ' ('+bestPlayer.drafted_team_acro+ ')' : '')}}
                        </sup>
                        <div class="flex justify-center space-x-3 items-center">
                            <sup v-if="bestPlayer?.is_finals_mvp" title="Finals MVP">
                                <i class="fa fa-trophy text-yellow-500 text-lg"></i>
                            </sup>
                            <sup v-if="bestPlayer?.is_season_mvp" title="Season MVP">
                                <i class="fa fa-star text-yellow-500 text-lg"></i>
                            </sup>
                            <sup v-if="bestPlayer?.is_defensive_poy" title="Defensive Player of the Season">
                                <i class="fa fa-shield-alt text-blue-500 text-lg"></i>
                            </sup>
                            <sup v-if="bestPlayer?.is_rookie_poy" title="Rookie of the Season">
                                 <b class="text-green-500 text-lg text-bold bg-green-200 rounded-full p-1 text-center">R</b>
                            </sup>
                            <sup v-if="bestPlayer?.is_most_improved" title="Most Improved Player of the Season">
                                <i class="fa fa-chart-line text-purple-500 text-lg"></i>
                            </sup>
                            <sup v-if="bestPlayer?.is_sixth_man" title="Sixth Man of the Season">
                                <b class="text-gray-500 text-lg text-bold bg-gray-200 rounded-full p-1 text-center">6</b>
                            </sup>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Stat Leaders Section: 3/4 Width -->
            <div class="w-full md:w-1/2 p-2 bg-white">
                <h3 class="text-lg font-semibold mb-2">Stat Leaders</h3>
                <div class="min-w-full shadow-lg border-gray-300 p-4">
                    <ul class="space-y-4">
                        <li
                            v-if="statLeaders.points"
                            class="flex items-center border-b border-gray-300 pb-2"
                        >
                            <span
                                class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"
                            >
                                <i
                                    class="fas fa-basketball-ball text-gray-600"
                                ></i>
                            </span>
                            <div class="ml-3 flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-bold">{{
                                            statLeaders.points.player_name
                                        }}</span>
                                        <small class="text-gray-400 block">{{
                                            statLeaders.points.team_name
                                        }}</small>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-2xl">
                                            {{ statLeaders.points.points }} pts
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li
                            v-if="statLeaders.assists"
                            class="flex items-center border-b border-gray-300 pb-2"
                        >
                            <span
                                class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"
                            >
                                <i class="fas fa-hand-point-right text-gray-600" title="Assist"></i>
                            </span>
                            <div class="ml-3 flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-semibold">{{
                                            statLeaders.assists.player_name
                                        }}</span>
                                        <small class="text-gray-400 block">{{
                                            statLeaders.assists.team_name
                                        }}</small>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-2xl">
                                            {{
                                                statLeaders.assists.assists
                                            }}
                                            ast
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li
                            v-if="statLeaders.rebounds"
                            class="flex items-center border-b border-gray-300 pb-2"
                        >
                            <span
                                class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"
                            >
                                <i class="fas fa-arrow-alt-circle-up text-gray-600" title="Rebounds"></i>
                            </span>
                            <div class="ml-3 flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-semibold">{{
                                            statLeaders.rebounds.player_name
                                        }}</span>
                                        <small class="text-gray-400 block">{{
                                            statLeaders.rebounds.team_name
                                        }}</small>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-2xl">
                                            {{
                                                statLeaders.rebounds.rebounds
                                            }}
                                            reb
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li
                            v-if="statLeaders.steals"
                            class="flex items-center border-b border-gray-300 pb-2"
                        >
                            <span
                                class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"
                            >
                            <i class="fas fa-user-shield text-gray-600" title="Steals"></i>
                            </span>
                            <div class="ml-3 flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-semibold">{{
                                            statLeaders.steals.player_name
                                        }}</span>
                                        <small class="text-gray-400 block">{{
                                            statLeaders.steals.team_name
                                        }}</small>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-2xl">
                                            {{ statLeaders.steals.steals }} stl
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li
                            v-if="statLeaders.blocks"
                            class="flex items-center border-b border-gray-300 pb-2"
                        >
                            <span
                                class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center"
                            >
                                <i class="fas fa-stop-circle text-gray-600" title="Blocks"></i>
                            </span>
                            <div class="ml-3 flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-semibold">{{
                                            statLeaders.blocks.player_name
                                        }}</span>
                                        <small class="text-gray-400 block">{{
                                            statLeaders.blocks.team_name
                                        }}</small>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-2xl">
                                            {{ statLeaders.blocks.blocks }} blk
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div class="p-0 flex-grow mt-2" v-if="injuredPlayers?.length > 0">
                        <div class="border-l-4 border-red-500 h-full overflow-hidden">
                            <div class="flex items-center h-6 px-2">
                                <span class="animate-pulse flex items-center">
                                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                                    <span class="font-bold text-red-500 text-sm">BREAKING NEWS:</span>
                                </span>
                            </div>
                            <div class="h-10 overflow-hidden px-2">
                                <div class="whitespace-nowrap animate-marquee flex text-sm text-gray-800">
                                    <div class="flex items-center">
                                        <div
                                            v-for="injury in injuredPlayers"
                                            :key="injury.id"
                                            class="flex-shrink-0 inline-block"
                                        >
                                            {{ formatInjuredPlayers(injury) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fixed bottom-0 right-24 bg-gray-900 text-white p-2 rounded-l shadow-lg z-50" v-if="!showBoxScore">
            <div class="flex items-center space-x-2">
                <i class="fas fa-clock"></i>
                
                <span class="font-mono">{{ formatTime(time) }}</span>
            </div>
        </div>
    </div>
    <Modal :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Profile" @close="showPlayerProfileModal = false">
        <div class="p-6 block">
            <PlayerPerformance
                :key="showPlayerProfileModal.player_id"
                :player_id="showPlayerProfileModal.player_id"
            />
        </div>
    </Modal>
   
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import axios from "axios"; 
import { roundNameFormatter, roleBadgeClass, playerFormatter } from "@/Utility/Formatter";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

const props = defineProps({
    game_id: {
        type: String,
        required: true,
    },
    showBoxScore: {
        type: Boolean,
        default: true,
    },
});
const showPlayerProfileModal = ref(false);
const isTeamRosterModalOpen = ref(false);
const gameDetails = ref(false);
const playerStats = ref({ home: [], away: [] });
const bestPlayer = ref(null);
const statLeaders = ref([]);
const injuredPlayers =ref([]);
const seasonLeaders = ref([]);
// Fetch the box score data
const time = ref(0); // Timer in seconds
const interval = ref(null); // Stores interval ID
const gameFinished = ref(false); // Flag for game completion

// Start the timer
const startTimer = () => {
  if (interval.value) return; // Prevent multiple intervals running

  interval.value = setInterval(() => {
    time.value++;
  }, 1000);
}

// Stop the timer
const stopTimer = () => {
  if (interval.value) {
    clearInterval(interval.value);
    interval.value = null; // Reset interval
  }
}

// Format time to mm:ss
const  formatTime  = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

const fetchBoxScore = async () => {
    try {
        gameFinished.value = false; // Reset the game status
        startTimer(); // Start the timer
        gameDetails.value = false;
        const response = await axios.post(route("game.boxscore"), {
            game_id: props.game_id,
        });
        const data = response.data.box_score;

        gameDetails.value = data;
        playerStats.value.home = data.player_stats.home;
        playerStats.value.away = data.player_stats.away;
        bestPlayer.value = data.best_player;
        statLeaders.value = data.stat_leaders;
        injuredPlayers.value = data.injury;
        seasonLeaders.value = data.league_leaders;
        gameFinished.value = true;
        // stopTimer(); // Stop the timer when the game finishes
    } catch (error) {
        console.error("Error fetching box score:", error);
    }
};

onUnmounted(() => {
    stopTimer();
});

// Sort players by points and get top 5 players
const sortedHomePlayers = computed(() => {
    return playerStats.value.home.slice().sort((a, b) => b.points - a.points);
});

const sortedAwayPlayers = computed(() => {
    return playerStats.value.away.slice().sort((a, b) => b.points - a.points);
});

const top5HomePlayers = computed(() => {
    return sortedHomePlayers.value.slice(0, 5).map((player) => player.name);
});

const top5AwayPlayers = computed(() => {
    return sortedAwayPlayers.value.slice(0, 5).map((player) => player.name);
});

const messageMap = new Map(); // Store message format for each player

const formatInjuredPlayers = (player) => {
    // If player already has a message format, use it
    if (!messageMap.has(player.player_id)) {
        const messages = [
            `🚨 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | ${player.recovery_games} days`,
            `⚠️ ${player.team_when_injured}'s ${player.player_name} ${player.position} - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | ${player.recovery_games} days`,
            `🏥 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | ${player.recovery_games} days`,
            `⛔ ${player.team_when_injured} loses ${player.player_name} ${player.position} - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | ${player.recovery_games} days`
        ];

        // Pick a random message format
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        messageMap.set(player.player_id, randomMessage); // Store the message format
    }

    return messageMap.get(player.player_id) + ' • '; // Add bullet point separator
};

onMounted(() => {
    fetchBoxScore();
});
</script>

<style scoped>
.team-card {
    background-color: #1a202c; /* Dark background for team cards */
    transition: transform 0.2s;
}

.team-card:hover {
    transform: scale(1.05); /* Scale effect on hover */
}

/* Use darker backgrounds for table headers */
table {
    border-collapse: collapse;
}

th,
td {
    border: 1px solid #2d3748; /* Subtle borders */
}

tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.1); /* Light hover effect */
}

.fixed {
    transition: opacity 0.3s ease-in-out;
}

.fixed:hover {
    opacity: 0.8;
}

@keyframes marquee {
  0% {
    transform: translateX(100%);
  }
  100% {
    transform: translateX(-100%);
  }
}

.animate-marquee {
  display: inline-block;
  animation: marquee 10s linear infinite;
}

/* Add these new styles for skeleton animation */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* Add these new styles */
.truncate {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
