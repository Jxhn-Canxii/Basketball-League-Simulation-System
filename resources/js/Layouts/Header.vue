<template>
    <header
        class="sticky top-0 z-40 flex h-14 min-w-0 items-center border-b border-gray-800 bg-gray-950 px-3 shadow-lg sm:px-4"
    >
        <!-- Mobile Menu -->
        <button
            type="button"
            @click.prevent="
                $page.props.showingMobileMenu =
                    !$page.props.showingMobileMenu
            "
            class="mr-3 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-800 bg-gray-900 text-gray-400 transition hover:border-gray-700 hover:bg-gray-800 hover:text-white focus:outline-none lg:hidden"
            aria-label="Toggle navigation menu"
        >
            <svg
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M4 6H20M4 12H20M4 18H11"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </button>

        <!-- Brand -->
        <div class="flex shrink-0 items-center gap-2">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-500/20 bg-rose-500/10"
            >
                <i class="fa fa-basketball text-sm text-rose-500"></i>
            </div>

            <div class="hidden sm:block">
                <div
                    class="text-xs font-bold uppercase tracking-wider text-white"
                >
                    LIGA
                    <span class="text-rose-500">PILIPINAS</span>
                </div>

                <div
                    class="text-[9px] uppercase tracking-widest text-gray-600"
                >
                    League Management
                </div>
            </div>
        </div>

        <!-- Desktop Navigation -->
        <nav
            class="ml-4 hidden min-w-0 flex-1 items-center gap-1 lg:flex"
        >
            <!-- Records -->
            <div class="relative">
                <button
                    type="button"
                    @click.prevent="toggleDropdown('history')"
                    :class="navButtonClass('history')"
                >
                    <span>Records</span>
                    <i
                        class="fa fa-chevron-down text-[8px] transition-transform"
                        :class="{
                            'rotate-180': activeDropdown === 'history',
                        }"
                    ></i>
                </button>

                <div
                    v-if="activeDropdown === 'history'"
                    class="dropdown-panel"
                >
                    <div class="dropdown-label">
                        Records
                    </div>

                    <NavLink
                        :href="route('leaders.index')"
                        :active="route().current('leaders.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-chart-line"></i>
                        <span>Statistical Leaders</span>
                    </NavLink>

                    <NavLink
                        :href="route('records.index')"
                        :active="route().current('records.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-trophy"></i>
                        <span>Historical Records</span>
                    </NavLink>

                    <NavLink
                        :href="route('experience.index')"
                        :active="route().current('experience.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-medal"></i>
                        <span>Playoff Records</span>
                    </NavLink>

                    <NavLink
                        :href="route('awards.index')"
                        :active="route().current('awards.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-award"></i>
                        <span>Season Awards</span>
                    </NavLink>

                    <NavLink
                        :href="route('analytics.index')"
                        :active="route().current('analytics.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-chart-pie"></i>
                        <span>League Analytics</span>
                    </NavLink>
                </div>
            </div>

            <!-- Players & Coaches -->
            <div class="relative">
                <button
                    type="button"
                    @click.prevent="toggleDropdown('players')"
                    :class="navButtonClass('players')"
                >
                    <span>Players & Coaches</span>
                    <i
                        class="fa fa-chevron-down text-[8px] transition-transform"
                        :class="{
                            'rotate-180': activeDropdown === 'players',
                        }"
                    ></i>
                </button>

                <div
                    v-if="activeDropdown === 'players'"
                    class="dropdown-panel"
                >
                    <div class="dropdown-label">
                        Personnel
                    </div>

                    <NavLink
                        :href="route('players.index')"
                        :active="route().current('players.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-users"></i>
                        <span>Players</span>
                    </NavLink>

                    <NavLink
                        :href="route('freeagents.index')"
                        :active="route().current('freeagents.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-user-plus"></i>
                        <span>Free Agents</span>
                    </NavLink>

                    <NavLink
                        :href="route('coaches.index')"
                        :active="route().current('coaches.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-user-tie"></i>
                        <span>Coaches</span>
                    </NavLink>
                </div>
            </div>

            <!-- Teams & Leagues -->
            <div class="relative">
                <button
                    type="button"
                    @click.prevent="toggleDropdown('teams')"
                    :class="navButtonClass('teams')"
                >
                    <span>Teams & Leagues</span>
                    <i
                        class="fa fa-chevron-down text-[8px] transition-transform"
                        :class="{
                            'rotate-180': activeDropdown === 'teams',
                        }"
                    ></i>
                </button>

                <div
                    v-if="activeDropdown === 'teams'"
                    class="dropdown-panel"
                >
                    <div class="dropdown-label">
                        Organization
                    </div>

                    <NavLink
                        :href="route('teams.index')"
                        :active="route().current('teams.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-shield-halved"></i>
                        <span>Teams</span>
                    </NavLink>

                    <NavLink
                        :href="route('leagues.index')"
                        :active="route().current('leagues.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-globe"></i>
                        <span>Leagues</span>
                    </NavLink>
                </div>
            </div>

            <!-- Seasons -->
            <div class="relative">
                <button
                    type="button"
                    @click.prevent="toggleDropdown('seasons')"
                    :class="navButtonClass('seasons')"
                >
                    <span>Seasons</span>
                    <i
                        class="fa fa-chevron-down text-[8px] transition-transform"
                        :class="{
                            'rotate-180': activeDropdown === 'seasons',
                        }"
                    ></i>
                </button>

                <div
                    v-if="activeDropdown === 'seasons'"
                    class="dropdown-panel"
                >
                    <div class="dropdown-label">
                        League History
                    </div>

                    <NavLink
                        :href="route('seasons.index')"
                        :active="route().current('seasons.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-calendar"></i>
                        <span>Seasons</span>
                    </NavLink>
                </div>
            </div>

            <!-- Users -->
            <div class="relative">
                <button
                    type="button"
                    @click.prevent="toggleDropdown('users')"
                    :class="navButtonClass('users')"
                >
                    <span>Users</span>
                    <i
                        class="fa fa-chevron-down text-[8px] transition-transform"
                        :class="{
                            'rotate-180': activeDropdown === 'users',
                        }"
                    ></i>
                </button>

                <div
                    v-if="activeDropdown === 'users'"
                    class="dropdown-panel"
                >
                    <div class="dropdown-label">
                        Administration
                    </div>

                    <NavLink
                        :href="route('users.index')"
                        :active="route().current('users.index')"
                        class="dropdown-link"
                    >
                        <i class="fa fa-user-gear"></i>
                        <span>Users</span>
                    </NavLink>
                </div>
            </div>
        </nav>

        <!-- Right Side -->
        <div class="ml-auto flex shrink-0 items-center gap-2">
            <!-- Current Section Indicator -->
            <div
                class="hidden items-center gap-2 rounded-lg border border-gray-800 bg-gray-900 px-3 py-1.5 xl:flex"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>

                <span
                    class="text-[10px] font-medium uppercase tracking-wider text-gray-500"
                >
                    League
                </span>
            </div>

            <!-- Logout -->
            <DropdownLink
                :href="route('logout')"
                method="post"
                as="button"
                class="group flex h-9 w-9 items-center justify-center rounded-lg border border-gray-800 bg-gray-900 p-0 text-gray-500 transition hover:border-red-500/30 hover:bg-red-500/10 hover:text-red-400"
                title="Logout"
            >
                <i
                    class="fa fa-power-off text-xs transition-transform group-hover:scale-110"
                ></i>
            </DropdownLink>
        </div>
    </header>
</template>

<script setup>
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import { ref, onMounted, onBeforeUnmount } from "vue";

const activeDropdown = ref(null);

/*
|--------------------------------------------------------------------------
| Dropdown
|--------------------------------------------------------------------------
*/

const toggleDropdown = (dropdown) => {
    activeDropdown.value =
        activeDropdown.value === dropdown ? null : dropdown;
};

const closeDropdowns = () => {
    activeDropdown.value = null;
};

const handleDocumentClick = (event) => {
    const target = event.target;

    if (!target.closest(".relative")) {
        closeDropdowns();
    }
};

/*
|--------------------------------------------------------------------------
| Navigation Button Styling
|--------------------------------------------------------------------------
*/

const navButtonClass = (dropdown) => {
    const active = activeDropdown.value === dropdown;

    return [
        "flex items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-medium transition-all duration-150",
        active
            ? "bg-rose-500/10 text-rose-400"
            : "text-gray-400 hover:bg-gray-900 hover:text-white",
    ];
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleDocumentClick);
});
</script>

<style scoped>
.dropdown-panel {
    @apply absolute left-0 top-full z-50 mt-2 min-w-[210px] rounded-xl border border-gray-800 bg-gray-950 p-1.5 shadow-2xl;
}

.dropdown-label {
    @apply px-3 pb-1.5 pt-2 text-[9px] font-semibold uppercase tracking-widest text-gray-600;
}

.dropdown-link {
    @apply flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[11px] text-gray-400 transition-colors hover:bg-gray-900 hover:text-white;
}

.dropdown-link :deep(i) {
    @apply w-4 text-center text-[10px] text-gray-600;
}

.dropdown-link:hover :deep(i) {
    @apply text-rose-400;
}
</style>