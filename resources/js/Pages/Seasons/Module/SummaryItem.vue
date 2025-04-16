// SummaryItem.vue component
<template>
  <div class="flex items-center space-x-2 p-2 bg-white rounded-lg shadow-sm">
    <i :class="['fas', `fa-${icon}`, 'text-gray-400']"></i>
    <span class="text-sm text-gray-600">{{ label }}:</span>
    <TeamDetails
      :team_id="teamId"
      :showButton="0"
      :text="teamName"
      class="font-medium text-gray-900"
    />
  </div>
</template>

<script setup>
defineProps({
  icon: String,
  label: String,
  teamId: Number,
  teamName: String
});
</script>