<template>
    <div>
        <span
            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold"
            :class="
                enabled
                    ? 'border-amber-400/20 bg-amber-400/10 text-amber-300'
                    : 'border-white/[0.07] bg-white/[0.025] text-slate-500'
            "
        >
            <span
                class="h-1.5 w-1.5 rounded-full"
                :class="enabled ? 'bg-amber-400' : 'bg-slate-600'"
            ></span>

            {{ enabled ? 'Yes' : 'None' }}
        </span>  
    </div>
</template>
<script setup>
    const props = defineProps({
        label: {
            type: String,
            required: true,
        },
        enable: {
            type: Number,
            default: false,
        }
    });
</script>