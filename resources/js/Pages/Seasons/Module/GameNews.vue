<template>
  <div :class="textLarge ? 'max-w-lg' : 'max-w-sm'" class="flex p-4 rounded-2xl shadow-lg bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white flex flex-col mt-4 min-w-full relative z-9000">
    <div :class="textLarge ? 'text-xl' : 'text-lg'" class="font-bold mb-2 border-b border-white/30 pb-1">
      {{ data.title }}
      <br>
      <a class="text-sm text-right text-yellow-400" @click.prevent="show_news = !show_news"> Show {{ show_news ? 'less' : 'more' }}...</a>
    </div>
    <p v-if="show_news" :class="textLarge ? 'text-lg' : 'text-sm'" class=" leading-relaxed text-white/90">
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
    },
    showNews:{
      type: Boolean,
      default: false,
    },
    textLarge:{
      type: Boolean,
      default: false,
    }
});

const loadData = () => {
    show_news.value = props.showNews;
}
onMounted(() => {
   loadData();
})
</script>
