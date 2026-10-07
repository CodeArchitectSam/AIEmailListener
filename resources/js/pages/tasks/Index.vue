<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PriorityBadge from '@/components/maintenance/PriorityBadge.vue';
import StatusBadge from '@/components/maintenance/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import type { TaskListItem } from '@/types/maintenance';

type Props = {
    tasks: {
        data: TaskListItem[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            links: Record<string, string>;
        };
    };
    filters: {
        status: string | null;
        priority: string | null;
        category: string | null;
    };
    options: {
        statuses: string[];
        priorities: string[];
        categories: string[];
    };
};

const props = defineProps<Props>();

const status = ref(props.filters.status ?? '');
const priority = ref(props.filters.priority ?? '');
const category = ref(props.filters.category ?? '');
const loading = ref(false);

const applyFilters = () => {
    router.get(
        '/tasks',
        {
            status: status.value || undefined,
            priority: priority.value || undefined,
            category: category.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    status.value = '';
    priority.value = '';
    category.value = '';
    applyFilters();
};

const hasFilters = computed(() => Boolean(status.value || priority.value || category.value));

let stopStart: () => void = () => {};
let stopFinish: () => void = () => {};

onMounted(() => {
    stopStart = router.on('start', () => {
        loading.value = true;
    });
    stopFinish = router.on('finish', () => {
        loading.value = false;
    });
});

onUnmounted(() => {
    stopStart();
    stopFinish();
});

const selectClass =
    'h-9 rounded-md border bg-background px-3 text-sm text-foreground outline-none focus:ring-2 focus:ring-ring';
</script>

<template>
    <Head title="Maintenance Tasks" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading title="Maintenance Tasks" />
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span class="font-medium text-foreground">{{ tasks.meta.total }}</span>
                task(s) total
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm">
                Status
                <select v-model="status" class="min-w-36" :class="selectClass" @change="applyFilters">
                    <option value="">All</option>
                    <option v-for="value in options.statuses" :key="value" :value="value">{{ value }}</option>
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm">
                Priority
                <select v-model="priority" class="min-w-36" :class="selectClass" @change="applyFilters">
                    <option value="">All</option>
                    <option v-for="value in options.priorities" :key="value" :value="value">{{ value }}</option>
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm">
                Category
                <select v-model="category" class="min-w-40" :class="selectClass" @change="applyFilters">
                    <option value="">All</option>
                    <option v-for="value in options.categories" :key="value" :value="value">{{ value }}</option>
                </select>
            </label>
            <Button v-if="hasFilters" variant="ghost" size="sm" @click="clearFilters">Clear filters</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-xs text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Task</th>
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Priority</th>
                        <th class="px-4 py-3 font-medium">Location</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-center font-medium">Reports</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading" v-for="i in 5" :key="i">
                        <td v-for="j in 7" :key="j" class="px-4 py-3">
                            <Skeleton class="h-4 w-full" />
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="task in tasks.data"
                        :key="task.id"
                        class="border-b last:border-b-0 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 whitespace-nowrap">
                            <Link :href="`/tasks/${task.id}`" class="font-medium text-primary hover:underline">
                                {{ task.task_number }}
                            </Link>
                        </td>
                        <td class="max-w-xs truncate px-4 py-3">{{ task.title }}</td>
                        <td class="px-4 py-3">{{ task.category }}</td>
                        <td class="px-4 py-3">
                            <PriorityBadge :value="task.priority" />
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="task.location.building || task.location.floor || task.location.room">
                                {{
                                    [task.location.building, task.location.floor, task.location.room]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :value="task.status" />
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-muted px-2 text-xs"
                            >
                                {{ task.emails_count }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!loading && tasks.data.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">No tasks found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tasks.meta.last_page > 1" class="flex items-center justify-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="tasks.meta.current_page === 1"
                :href="tasks.meta.links[Math.max(1, tasks.meta.current_page - 1)] ?? '#'"
                as="a"
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ tasks.meta.current_page }} of {{ tasks.meta.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="tasks.meta.current_page === tasks.meta.last_page"
                :href="tasks.meta.links[Math.min(tasks.meta.last_page, tasks.meta.current_page + 1)] ?? '#'"
                as="a"
            >
                Next
            </Button>
        </div>
    </div>
</template>