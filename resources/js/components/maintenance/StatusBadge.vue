<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    value: string;
}>();

const classes = computed(() => {
    switch (props.value) {
        case 'Open':
        case 'In Progress':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200';
        case 'Needs Review':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200';
        case 'Resolved':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
});
</script>

<template>
    <span
        class="inline-flex w-fit items-center rounded-md border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="classes"
    >
        {{ value }}
    </span>
</template>