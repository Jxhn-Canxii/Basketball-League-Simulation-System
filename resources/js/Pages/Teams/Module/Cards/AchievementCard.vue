<template>
    <div
        class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 min-h-[150px]"
    >
        <div
            class="flex items-center gap-2 mb-4"
        >
            <i
                :class="icon"
                class="text-xs"
                :style="{ color: accent }"
            ></i>

            <span
                class="text-[10px] uppercase tracking-wider font-bold text-white/45"
            >
                {{ title }}
            </span>
        </div>

        <div
            v-if="items?.length"
            class="flex flex-wrap gap-1.5"
        >
            <span
                v-for="(season, index) in items"
                :key="index"
                class="px-2 py-1 rounded-lg text-[10px] font-bold border"
                :style="{
                    color: accent,
                    borderColor: accent + '30',
                    backgroundColor: accent + '12'
                }"
            >
                {{ season }}
            </span>
        </div>

        <div
            v-else
            class="h-20 flex items-center justify-center text-center text-xs text-white/20"
        >
            No seasons recorded
        </div>
    </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { roundNameFormatter, numberFormatter } from "@/Utility/Formatter";

const props = defineProps({
    eyebrow: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
        required: true,
    },
    accent: {
        type: String,
        required: true,
    },
    danger: {
        type: Boolean,
        required: true,
    },
});
</script>