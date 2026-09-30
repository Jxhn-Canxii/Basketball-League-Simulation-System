<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.035] p-4"
    >
        <div
            class="absolute top-0 left-0 right-0 h-px opacity-50"
            :style="{ backgroundColor: accent }"
        ></div>

        <div class="flex items-center gap-2 mb-4">
            <i
                :class="icon"
                class="text-xs"
                :style="{ color: accent }"
            ></i>

            <span
                class="text-[10px] uppercase tracking-widest font-bold text-white/40"
            >
                {{ title }}
            </span>
        </div>

        <slot></slot>
    </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { roundNameFormatter, numberFormatter } from "@/Utility/Formatter";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  accent: {
    type: String,
    required: true,
  },
});
</script>
