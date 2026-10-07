<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    value: string;
}>();

const classes = computed(() => {
    switch (props.value) {
        case 'Emergency':
            return 'border-transparent bg-destructive text-white';
        case 'High':
            return 'border-transparent bg-orange-500 text-white';
        case 'Medium':
            return 'border-transparent bg-amber-400 text-amber-950';
        default:
            return 'border-transparent bg-emerald-500 text-emerald-950';
    }
});
</script>

<template>
    <span
        class="inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="classes"
    >
        {{ value }}
    </span>
</template>