<template>
  <div ref="trigger" class="relative inline-block" @mouseenter="show = true" @mouseleave="show = false">
    <slot />
    <transition name="fade">
      <div
        v-if="show"
        ref="tooltip"
        class="fixed z-[9999] bg-gray-900 text-white text-xs rounded shadow-lg p-3 w-auto inline-block whitespace-pre-line"
        :style="tooltipStyle"
      >
        <!-- Arrow tip -->
        <span
          class="absolute left-[-8px] top-1/2 -translate-y-1/2 w-0 h-0 border-t-8 border-b-8 border-r-8 border-t-transparent border-b-transparent border-r-gray-900"
          style="z-index: 10000;"
        ></span>
        <slot name="content">
          <span v-html="content"></span>
        </slot>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
const props = defineProps({
  content: {
    type: String,
    default: '',
  },
});
const show = ref(false);
const tooltipStyle = ref({ left: '50%', transform: 'translateX(-50%)', top: '100%' });
const trigger = ref(null);
const tooltip = ref(null);

const adjustPosition = () => {
  if (!trigger.value || !tooltip.value) return;
  const triggerRect = trigger.value.getBoundingClientRect();
  const tooltipRect = tooltip.value.getBoundingClientRect();
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;

  // Default: show to the right and vertically centered to trigger
  let top = triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2;
  let left = triggerRect.right + 16;

  // If not enough space right, show left
  if (left + tooltipRect.width > viewportWidth) {
    left = triggerRect.left - tooltipRect.width - 16;
  }
  // If not enough space left, clamp to left edge
  if (left < 8) left = 8;

  // Clamp top to viewport
  if (top < 8) top = 8;
  if (top + tooltipRect.height > viewportHeight - 8) top = viewportHeight - tooltipRect.height - 8;

  tooltipStyle.value = {
    left: `${left}px`,
    top: `${top}px`,
    transform: '',
  };
};

watch(show, async (val) => {
  if (val) await nextTick();
  adjustPosition();
});
onMounted(() => {
  window.addEventListener('resize', adjustPosition);
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
