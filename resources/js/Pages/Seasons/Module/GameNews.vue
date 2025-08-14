<template>
  <div class="p-4 rounded-2xl shadow-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white w-full max-w-sm z-1000">
    <h2 class="text-lg font-bold mb-2 border-b border-white/30 pb-1">
      {{ data.title }}
    </h2>
    <p class="text-sm leading-relaxed text-white/90">
      {{ data.content }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      title: '',
      body: ''
    })
  }
});
</script>
