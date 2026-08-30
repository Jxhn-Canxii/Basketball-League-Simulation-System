<template>
  <div class="p-4 rounded-2xl shadow-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white w-full max-w-sm relative z-9000">
    <div class="text-lg font-bold mb-2 border-b border-white/30 pb-1">
      {{ data.title }}
      <br>
      <a class="text-sm text-right text-yellow-400" @click.prevent="show_news = !show_news"> Show {{ show_news ? 'less' : 'more' }}...</a>
    </div>
    <p v-if="show_news" class="text-sm leading-relaxed text-white/90">
      {{ data.content }}
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const show_news = ref(false);
const props = defineProps({
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
