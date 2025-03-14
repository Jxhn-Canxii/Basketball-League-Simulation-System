<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";

const DEFAULT_BG = ref('bg-slate-900');
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: "2xl",
  },
  title: {
    type: String,
    default: "Modal",
  },
  closeable: {
    type: Boolean,
    default: true,
  },
});

const borderForSmallModal = 'shadow-b-8 shadow-l-8 border-b-4 border-l-4 border-t-2 border-b-gray-300 border-l-gray-300 border-t-gray-400 shadow-2xl shadow-gray-600';

const maxWidthClass = computed(() => {
  return {
    sm: "sm:max-w-sm "+borderForSmallModal,
    md: "sm:max-w-md "+borderForSmallModal,
    lg: "sm:max-w-lg "+borderForSmallModal,
    xl: "sm:max-w-xl "+borderForSmallModal,
    "2xl": "sm:max-w-2xl "+borderForSmallModal,
    "4xl": "sm:max-w-4xl "+borderForSmallModal,
    "6xl": "sm:max-w-6xl "+borderForSmallModal,
    "8xl": "sm:max-w-8xl "+borderForSmallModal,
    fullscreen: "absolute top-0 mt-0 pt-0 h-screen w-screen",
  }[props.maxWidth];
});

const emit = defineEmits(["close"]);

watch(
  () => props.show,
  () => {
    if (props.show) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = null;
    }
  }
);

const close = () => {
  if (props.closeable) {
    emit("close");
  }
};

const closeOnEscape = (e) => {
  if (e.key === "Escape" && props.show) {
    close();
  }
};

onMounted(() => document.addEventListener("keydown", closeOnEscape));

onUnmounted(() => {
  document.removeEventListener("keydown", closeOnEscape);
  document.body.style.overflow = null;
});
</script>

<template>
  <Teleport to="body">
    <Transition leave-active-class="duration-200">
      <div
        v-show="show"
        class="fixed inset-0 overflow-auto px-4 py-6 sm:px-0 z-50 min-h-full"
        scroll-region
      >
        <!-- Overlay -->
        <div class="fixed inset-0 opacity-50" :class="DEFAULT_BG" @click="close">

        </div>
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-show="show"
            class="mb-6 bg-white rounded-lg overflow-auto transform transition-all sm:w-full sm:mx-auto"
            :class="maxWidthClass"
          >
            <div class="flex justify-between px-3 py-2" :class="DEFAULT_BG">
              <h2 class="text-lg font-semibold text-white">
                {{ title }}
              </h2>
              <button
                class="flex float-end p-2 items-center justify-center bg-red-500 border-white border-2 animate-pulse rounded-full mr-4 md:mr-1 w-8 h-8"
                @click.prevent="close"
              >
                <i class="fa fa-times fa-xl font-bold text-white"></i>
              </button>

            </div>
            <slot v-if="show" />
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
