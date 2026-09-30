<template>
    <div
        class="rounded-2xl border border-dashed border-white/10 bg-white/[0.02] py-10 px-5 text-center"
    >
        <div
            class="w-10 h-10 mx-auto rounded-xl bg-white/5 flex items-center justify-center text-white/20"
        >
            <i class="fas fa-inbox"></i>
        </div>

        <p class="mt-3 text-sm text-white/30">
            {{ message }}
        </p>
    </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { roundNameFormatter, numberFormatter } from "@/Utility/Formatter";

const props = defineProps({
  message: {
    type: String,
    required: true,
  }
});
</script>
