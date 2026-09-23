<template>
    <div>
        <!-- Edit Button -->
        <button
            type="button"
            @click.prevent="fillForm"
            :disabled="isEditModalOpen"
            class="group inline-flex items-center gap-2 rounded-xl border border-yellow-500/20 bg-yellow-500/[0.08] px-3 py-2 text-xs font-black text-yellow-500 transition-all duration-200 hover:border-yellow-500/40 hover:bg-yellow-500/[0.13] hover:text-yellow-400 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-yellow-500/10 transition group-hover:bg-yellow-500/20"
            >
                <i class="fas fa-edit text-[10px]"></i>
            </span>

            <span>Edit</span>
        </button>

        <!-- Modal -->
        <Modal
            :show="isEditModalOpen"
            :maxWidth="'2xl'"
            title="Edit Team"
            @close="closeModal"
        >
            <div class="overflow-hidden rounded-2xl bg-[#0a0a0a] text-white">
                <!-- Header -->
                <div
                    class="border-b border-white/[0.07] bg-[#0d0d0d] px-5 py-4 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-yellow-500/20 bg-yellow-500/[0.07]"
                        >
                            <i class="fas fa-edit text-sm text-yellow-500"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-yellow-500"
                                ></span>

                                <span
                                    class="text-[9px] font-black uppercase tracking-[0.2em] text-yellow-500/70"
                                >
                                    Team Management
                                </span>
                            </div>

                            <h2
                                class="mt-1 truncate text-base font-black tracking-tight text-white"
                            >
                                Edit Team
                            </h2>

                            <p class="mt-0.5 text-[10px] text-gray-600">
                                Update the team's identity, league structure,
                                and branding.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form
                    class="px-5 py-5 sm:px-6 sm:py-6"
                    @submit.prevent="Update"
                >
                    <div class="space-y-6">
                        <!-- Team Identity -->
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <i
                                    class="fas fa-id-card text-[10px] text-yellow-500"
                                ></i>

                                <span class="section-label">
                                    Team Identity
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Name -->
                                <div>
                                    <label
                                        for="edit-team-name"
                                        class="field-label"
                                    >
                                        Team Name
                                    </label>

                                    <div class="relative">
                                        <i
                                            class="fas fa-shield-alt input-icon"
                                        ></i>

                                        <input
                                            id="edit-team-name"
                                            v-model="form.name"
                                            type="text"
                                            autocomplete="off"
                                            placeholder="Enter team name"
                                            :disabled="form.processing"
                                            class="dark-input pl-9"
                                        />
                                    </div>

                                    <InputError
                                        class="mt-1.5"
                                        :message="form.errors.name"
                                    />
                                </div>

                                <!-- Acronym -->
                                <div>
                                    <label
                                        for="edit-team-acronym"
                                        class="field-label"
                                    >
                                        Acronym
                                    </label>

                                    <div class="relative">
                                        <i
                                            class="fas fa-tag input-icon"
                                        ></i>

                                        <input
                                            id="edit-team-acronym"
                                            v-model="form.acronym"
                                            type="text"
                                            maxlength="4"
                                            autocomplete="off"
                                            placeholder="e.g. LAL"
                                            :disabled="form.processing"
                                            class="dark-input pl-9 uppercase tracking-widest"
                                        />
                                    </div>

                                    <div class="mt-1 flex justify-between">
                                        <InputError
                                            :message="form.errors.acronym"
                                        />

                                        <span
                                            class="ml-auto text-[8px] font-bold text-gray-700"
                                        >
                                            {{ form.acronym?.length ?? 0 }}/4
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- League Structure -->
                        <section
                            class="border-t border-white/[0.06] pt-5"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <i
                                    class="fas fa-sitemap text-[10px] text-yellow-500"
                                ></i>

                                <span class="section-label">
                                    League Structure
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- League -->
                                <div>
                                    <label
                                        for="edit-team-league"
                                        class="field-label"
                                    >
                                        League
                                    </label>

                                    <div class="relative">
                                        <i
                                            class="fas fa-basketball-ball input-icon"
                                        ></i>

                                        <select
                                            id="edit-team-league"
                                            v-model="form.league_id"
                                            :disabled="form.processing"
                                            class="dark-input appearance-none pl-9 pr-9"
                                            @change="
                                                conferenceDropdown(
                                                    form.league_id
                                                )
                                            "
                                        >
                                            <option
                                                value="0"
                                                class="bg-[#111111] text-gray-500"
                                            >
                                                Select League
                                            </option>

                                            <option
                                                v-for="league in leagues"
                                                :key="league.id"
                                                :value="league.id"
                                                class="bg-[#111111] text-white"
                                            >
                                                {{ league.name }}
                                            </option>
                                        </select>

                                        <i
                                            class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-gray-700"
                                        ></i>
                                    </div>

                                    <InputError
                                        class="mt-1.5"
                                        :message="form.errors.league_id"
                                    />
                                </div>

                                <!-- Conference -->
                                <div>
                                    <label
                                        for="edit-team-conference"
                                        class="field-label"
                                    >
                                        Conference
                                    </label>

                                    <div class="relative">
                                        <i
                                            class="fas fa-layer-group input-icon"
                                        ></i>

                                        <select
                                            id="edit-team-conference"
                                            v-model="form.conference_id"
                                            :disabled="
                                                form.processing ||
                                                !form.league_id ||
                                                String(form.league_id) ===
                                                    '0' ||
                                                conferenceLoading
                                            "
                                            class="dark-input appearance-none pl-9 pr-9"
                                        >
                                            <option
                                                value="0"
                                                class="bg-[#111111] text-gray-500"
                                            >
                                                {{
                                                    conferenceLoading
                                                        ? "Loading conferences..."
                                                        : "Select Conference"
                                                }}
                                            </option>

                                            <option
                                                v-for="conference in conferences"
                                                :key="conference.id"
                                                :value="conference.id"
                                                class="bg-[#111111] text-white"
                                            >
                                                {{ conference.name }}
                                            </option>
                                        </select>

                                        <i
                                            v-if="!conferenceLoading"
                                            class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-gray-700"
                                        ></i>

                                        <i
                                            v-else
                                            class="fas fa-spinner fa-spin pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-yellow-500"
                                        ></i>
                                    </div>

                                    <InputError
                                        class="mt-1.5"
                                        :message="form.errors.conference_id"
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- Team Branding -->
                        <section
                            class="border-t border-white/[0.06] pt-5"
                        >
                            <div
                                class="mb-3 flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <i
                                        class="fas fa-palette text-[10px] text-yellow-500"
                                    ></i>

                                    <span class="section-label">
                                        Team Branding
                                    </span>
                                </div>

                                <span
                                    class="text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                >
                                    Primary / Secondary
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Primary -->
                                <div
                                    class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3.5"
                                >
                                    <div
                                        class="mb-3 flex items-center justify-between"
                                    >
                                        <div>
                                            <label
                                                for="edit-primary-color"
                                                class="text-[10px] font-black uppercase tracking-wider text-gray-400"
                                            >
                                                Primary Color
                                            </label>

                                            <p
                                                class="mt-0.5 text-[8px] text-gray-700"
                                            >
                                                Main team color
                                            </p>
                                        </div>

                                        <span
                                            class="h-7 w-7 rounded-lg border border-white/10 shadow-lg"
                                            :style="{
                                                backgroundColor:
                                                    normalizedColor(
                                                        form.primary_color
                                                    ),
                                            }"
                                        ></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input
                                            id="edit-primary-color"
                                            v-model="form.primary_color"
                                            type="color"
                                            :disabled="form.processing"
                                            class="color-picker"
                                            @input="
                                                handleColorPicker(
                                                    'primary_color'
                                                )
                                            "
                                        />

                                        <div class="relative flex-1">
                                            <span
                                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-600"
                                            >
                                                #
                                            </span>

                                            <input
                                                v-model="form.primary_color"
                                                type="text"
                                                maxlength="6"
                                                placeholder="0076B6"
                                                :disabled="form.processing"
                                                class="dark-input pl-7 uppercase tracking-widest"
                                                @input="
                                                    sanitizeColor(
                                                        'primary_color'
                                                    )
                                                "
                                            />
                                        </div>
                                    </div>

                                    <InputError
                                        class="mt-1.5"
                                        :message="
                                            form.errors.primary_color
                                        "
                                    />
                                </div>

                                <!-- Secondary -->
                                <div
                                    class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3.5"
                                >
                                    <div
                                        class="mb-3 flex items-center justify-between"
                                    >
                                        <div>
                                            <label
                                                for="edit-secondary-color"
                                                class="text-[10px] font-black uppercase tracking-wider text-gray-400"
                                            >
                                                Secondary Color
                                            </label>

                                            <p
                                                class="mt-0.5 text-[8px] text-gray-700"
                                            >
                                                Supporting team color
                                            </p>
                                        </div>

                                        <span
                                            class="h-7 w-7 rounded-lg border border-white/10 shadow-lg"
                                            :style="{
                                                backgroundColor:
                                                    normalizedColor(
                                                        form.secondary_color
                                                    ),
                                            }"
                                        ></span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input
                                            id="edit-secondary-color"
                                            v-model="form.secondary_color"
                                            type="color"
                                            :disabled="form.processing"
                                            class="color-picker"
                                            @input="
                                                handleColorPicker(
                                                    'secondary_color'
                                                )
                                            "
                                        />

                                        <div class="relative flex-1">
                                            <span
                                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-600"
                                            >
                                                #
                                            </span>

                                            <input
                                                v-model="form.secondary_color"
                                                type="text"
                                                maxlength="6"
                                                placeholder="E03A3E"
                                                :disabled="form.processing"
                                                class="dark-input pl-7 uppercase tracking-widest"
                                                @input="
                                                    sanitizeColor(
                                                        'secondary_color'
                                                    )
                                                "
                                            />
                                        </div>
                                    </div>

                                    <InputError
                                        class="mt-1.5"
                                        :message="
                                            form.errors.secondary_color
                                        "
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- Live Preview -->
                        <section>
                            <div
                                class="relative overflow-hidden rounded-xl border border-white/[0.07] bg-[#0d0d0d]"
                            >
                                <div
                                    class="absolute inset-0 opacity-[0.12]"
                                    :style="{
                                        background:
                                            `linear-gradient(135deg, ` +
                                            `${normalizedColor(form.primary_color)}, ` +
                                            `${normalizedColor(form.secondary_color)})`,
                                    }"
                                ></div>

                                <div
                                    class="relative flex items-center gap-3 px-4 py-4"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/[0.10] bg-black/40 shadow-lg"
                                    >
                                        <i
                                            class="fas fa-shield-alt text-base text-white/70"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-sm font-black text-white"
                                        >
                                            {{
                                                form.name?.trim()
                                                    ? form.name
                                                    : "Team Name"
                                            }}
                                        </div>

                                        <div
                                            class="mt-1 flex items-center gap-2"
                                        >
                                            <span
                                                class="text-[9px] font-black uppercase tracking-widest text-yellow-500"
                                            >
                                                {{
                                                    form.acronym?.trim()
                                                        ? form.acronym
                                                        : "TEAM"
                                                }}
                                            </span>

                                            <span class="text-gray-700">
                                                •
                                            </span>

                                            <span
                                                class="truncate text-[9px] text-gray-500"
                                            >
                                                {{ selectedLeagueName }}
                                            </span>

                                            <span
                                                v-if="selectedConferenceName"
                                                class="hidden truncate text-[9px] text-gray-600 sm:inline"
                                            >
                                                •
                                                {{ selectedConferenceName }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="hidden items-center gap-1.5 sm:flex"
                                    >
                                        <span
                                            class="h-6 w-6 rounded-full border-2 border-black shadow"
                                            :style="{
                                                backgroundColor:
                                                    normalizedColor(
                                                        form.primary_color
                                                    ),
                                            }"
                                        ></span>

                                        <span
                                            class="h-6 w-6 rounded-full border-2 border-black shadow"
                                            :style="{
                                                backgroundColor:
                                                    normalizedColor(
                                                        form.secondary_color
                                                    ),
                                            }"
                                        ></span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Footer -->
                    <div
                        class="mt-6 flex flex-col-reverse gap-2 border-t border-white/[0.06] pt-5 sm:flex-row sm:items-center sm:justify-end"
                    >
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="closeModal"
                            class="rounded-xl border border-white/[0.08] bg-white/[0.025] px-4 py-2.5 text-xs font-bold text-gray-500 transition hover:border-white/[0.14] hover:bg-white/[0.05] hover:text-gray-300 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-yellow-500/30 bg-yellow-500/[0.10] px-5 py-2.5 text-xs font-black text-yellow-500 transition hover:border-yellow-500/50 hover:bg-yellow-500/[0.16] hover:text-yellow-400 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <i
                                v-if="!form.processing"
                                class="fas fa-save text-[10px]"
                            ></i>

                            <i
                                v-else
                                class="fas fa-spinner fa-spin text-[10px]"
                            ></i>

                            <span>
                                {{
                                    form.processing
                                        ? "Saving..."
                                        : "Save Changes"
                                }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const isEditModalOpen = ref(false);

const leagues = ref([]);
const conferences = ref([]);
const conferenceLoading = ref(false);

const form = useForm({
    id: 0,
    name: "",
    acronym: "",
    league_id: 0,
    conference_id: 0,
    primary_color: "0076B6",
    secondary_color: "E03A3E",
});

const selectedLeagueName = computed(() => {
    const league = leagues.value.find(
        (item) =>
            String(item.id) === String(form.league_id)
    );

    return league?.name ?? "Select League";
});

const selectedConferenceName = computed(() => {
    const conference = conferences.value.find(
        (item) =>
            String(item.id) ===
            String(form.conference_id)
    );

    return conference?.name ?? "";
});

/*
|--------------------------------------------------------------------------
| Color Helpers
|--------------------------------------------------------------------------
*/

const normalizedColor = (color) => {
    if (!color) {
        return "#111111";
    }

    let value = String(color).trim();

    if (!value.startsWith("#")) {
        value = `#${value}`;
    }

    return value;
};

const sanitizeColor = (field) => {
    let value = form[field] ?? "";

    value = String(value)
        .replace("#", "")
        .replace(/[^0-9a-fA-F]/g, "")
        .substring(0, 6)
        .toUpperCase();

    form[field] = value;
};

/*
|--------------------------------------------------------------------------
| Native Color Picker
|--------------------------------------------------------------------------
|
| HTML color inputs require #RRGGBB.
| The database stores RRGGBB without #.
|
*/

const handleColorPicker = (field) => {
    let value = form[field] ?? "";

    value = String(value)
        .replace("#", "")
        .replace(/[^0-9a-fA-F]/g, "")
        .substring(0, 6)
        .toUpperCase();

    form[field] = value;
};

/*
|--------------------------------------------------------------------------
| Modal
|--------------------------------------------------------------------------
*/

const closeModal = () => {
    if (form.processing) {
        return;
    }

    isEditModalOpen.value = false;

    resetForm();
};

/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/

const resetForm = () => {
    form.reset();

    form.id = 0;
    form.name = "";
    form.acronym = "";
    form.league_id = 0;
    form.conference_id = 0;
    form.primary_color = "0076B6";
    form.secondary_color = "E03A3E";

    form.clearErrors();

    conferences.value = [];
    conferenceLoading.value = false;
};

/*
|--------------------------------------------------------------------------
| Load Leagues
|--------------------------------------------------------------------------
*/

const leagueDropdown = async () => {
    try {
        const response = await axios.get(
            route("leagues.dropdown")
        );

        leagues.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching leagues:",
            error
        );

        leagues.value = [];

        Swal.fire({
            title: "Unable to Load",
            text: "The league list could not be loaded.",
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Load Conferences
|--------------------------------------------------------------------------
*/

const conferenceDropdown = async (league_id) => {
    form.conference_id = 0;
    conferences.value = [];

    if (
        !league_id ||
        String(league_id) === "0"
    ) {
        return;
    }

    conferenceLoading.value = true;

    try {
        const response = await axios.post(
            route("conference.season.dropdown"),
            {
                league_id: league_id,
            }
        );

        conferences.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching conferences:",
            error
        );

        conferences.value = [];

        Swal.fire({
            title: "Unable to Load",
            text: "The conference list could not be loaded.",
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } finally {
        conferenceLoading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Fill Form
|--------------------------------------------------------------------------
*/

const fillForm = async () => {
    const data = props.data ?? {};

    form.clearErrors();

    form.id = data.id ?? 0;
    form.name = data.name ?? "";
    form.acronym = data.acronym ?? "";
    form.league_id = data.league_id ?? 0;
    form.conference_id = data.conference_id ?? 0;

    /*
     * Database stores colors without #.
     * We normalize them here so the color inputs
     * and preview work correctly.
     */
    form.primary_color = normalizeDatabaseColor(
        data.primary_color,
        "0076B6"
    );

    form.secondary_color = normalizeDatabaseColor(
        data.secondary_color,
        "E03A3E"
    );

    isEditModalOpen.value = true;

    /*
     * Load dropdown data when modal opens.
     */
    await leagueDropdown();

    if (
        form.league_id &&
        String(form.league_id) !== "0"
    ) {
        await conferenceDropdown(
            form.league_id
        );

        /*
         * conferenceDropdown resets conference_id,
         * so restore the team's original conference.
         */
        form.conference_id =
            data.conference_id ?? 0;
    }
};

/*
|--------------------------------------------------------------------------
| Normalize Existing Database Color
|--------------------------------------------------------------------------
*/

const normalizeDatabaseColor = (
    color,
    fallback
) => {
    if (!color) {
        return fallback;
    }

    return String(color)
        .replace("#", "")
        .replace(/[^0-9a-fA-F]/g, "")
        .substring(0, 6)
        .toUpperCase() || fallback;
};

/*
|--------------------------------------------------------------------------
| Update Team
|--------------------------------------------------------------------------
*/

const Update = async () => {
    if (form.processing) {
        return;
    }

    form.clearErrors();

    /*
     * Ensure colors are always stored as RRGGBB
     * without the # character.
     */
    sanitizeColor("primary_color");
    sanitizeColor("secondary_color");

    try {
        form.processing = true;

        const response = await axios.post(
            route("teams.update"),
            {
                id: form.id,
                name: form.name,
                acronym: form.acronym,
                league_id: form.league_id,
                conference_id: form.conference_id,
                primary_color:
                    form.primary_color,
                secondary_color:
                    form.secondary_color,
            }
        );

        if (response?.data) {
            await Swal.fire({
                title: "Team Updated",
                text: "Team information has been updated successfully.",
                icon: "success",
                background: "#0b0b0b",
                color: "#ffffff",
                confirmButtonColor: "#eab308",
            });

            isEditModalOpen.value = false;

            resetForm();

            emits(
                "transaction_id",
                Math.random()
            );
        }
    } catch (error) {
        console.error(
            "Error updating team:",
            error
        );

        /*
         * Laravel validation errors.
         */
        if (
            error?.response?.data?.errors
        ) {
            Object.entries(
                error.response.data.errors
            ).forEach(
                ([field, messages]) => {
                    form.setError(
                        field,
                        Array.isArray(messages)
                            ? messages[0]
                            : messages
                    );
                }
            );
        }

        const message =
            error?.response?.data?.message ||
            "Unable to update the team. Please check the form and try again.";

        Swal.fire({
            title: "Unable to Update Team",
            text: message,
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } finally {
        form.processing = false;
    }
};
</script>

<style scoped>
.section-label {
    @apply text-[9px] font-black uppercase tracking-[0.18em] text-gray-500;
}

.field-label {
    @apply mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500;
}

.input-icon {
    @apply pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-700;
}

.dark-input {
    @apply w-full rounded-xl border border-white/[0.08] bg-white/[0.035] py-2.5 text-xs font-medium text-white placeholder-gray-700 outline-none transition;
}

.dark-input:focus {
    border-color: rgb(234 179 8 / 0.4);
    background-color: rgb(255 255 255 / 0.05);
    box-shadow: 0 0 0 3px rgb(234 179 8 / 0.05);
}

.dark-input:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

:deep(input::placeholder) {
    color: rgb(55 65 81);
}

:deep(select option) {
    background-color: #111111;
    color: white;
}

/* Native color picker */
.color-picker {
    appearance: none;
    -webkit-appearance: none;

    width: 48px;
    height: 40px;

    flex-shrink: 0;

    cursor: pointer;

    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.5rem;

    background: transparent;
    padding: 4px;
}

.color-picker:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.color-picker::-webkit-color-swatch-wrapper {
    padding: 0;
}

.color-picker::-webkit-color-swatch {
    border: none;
    border-radius: 5px;
}

.color-picker::-moz-color-swatch {
    border: none;
    border-radius: 5px;
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}
</style>