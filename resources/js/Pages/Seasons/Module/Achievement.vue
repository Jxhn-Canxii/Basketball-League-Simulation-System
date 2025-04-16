// Achievement.vue component
<template>
  <div :class="[
    'flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium',
    typeClasses[type]
  ]" :title="typeLabels[type]">
    {{ count }}
  </div>
</template>

<script setup>
const props = defineProps({
  type: String,
  count: Number
});

const typeClasses = {
  championship: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
  conference: 'bg-gray-100 text-gray-800 border border-gray-300',
  overall: 'bg-blue-100 text-blue-800 border border-blue-300',
  conference_best: 'bg-green-100 text-green-800 border border-green-300',
  grandslam: 'bg-purple-100 text-purple-800 border border-purple-300'
};

const typeLabels = {
  championship: 'National Championships',
  conference: 'Conference Championships',
  overall: '#1 Overall Rank',
  conference_best: '#1 Conference Rank',
  grandslam: 'Grand Slam Champion'
};
</script>