<template>
     <div class="flex items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3 min-w-0">
            <div
                class="flex-shrink-0 w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/50"
            >
                <i :class="icon"></i>
            </div>

            <div class="min-w-0">
                <div
                    class="text-[9px] font-black tracking-[0.2em] text-white/25 uppercase"
                >
                    {{ eyebrow }}
                </div>

                <h2
                    class="text-lg sm:text-xl font-black truncate"
                >
                    {{ title }}
                </h2>
            </div>
        </div>

        <slot name="right"></slot>
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
});
</script>