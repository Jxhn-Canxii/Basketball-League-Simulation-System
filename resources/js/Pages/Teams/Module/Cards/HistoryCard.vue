<template>
  <div
    class="rounded-xl border p-3 min-w-0"
    :class="primary ? 'border-white/5' : 'border-white/10'"
    :style="
      primary
        ? {
            backgroundColor: primaryColor + '18',
          }
        : {
            backgroundColor: 'rgba(255,255,255,0.025)',
          }
    "
  >
    <div class="text-[9px] uppercase tracking-wider font-bold text-white/35 truncate">
      {{ label }}
    </div>

    <div
      class="mt-2 text-base font-black truncate"
      :style="
        primary
          ? {
              color: primaryColor,
            }
          : {}
      "
    >
      {{ value || "n/a" }}
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { roundNameFormatter, numberFormatter } from "@/Utility/Formatter";

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    value: String,
    required: true,
  },
  primaryColor: {
    type: String,
    required: true,
  },
  primary: {
    type: Boolean,
    required: true,
  },
});
</script>
