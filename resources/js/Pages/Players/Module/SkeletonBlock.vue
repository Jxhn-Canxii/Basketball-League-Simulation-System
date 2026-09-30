<template>
    <div
        :class="[
            'h-3 animate-pulse rounded bg-white/[0.07]',
            width
        ]"
    ></div>
</template>
<script setup>
    defineProps({
        width: {
            type: String,
            default: "w-20"
        }
    });
</script>