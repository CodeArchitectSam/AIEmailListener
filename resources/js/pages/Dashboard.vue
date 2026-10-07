<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import PriorityBadge from '@/components/maintenance/PriorityBadge.vue';
import StatusBadge from '@/components/maintenance/StatusBadge.vue';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

type CountRow = {
    key: string;
    count: number;
};

type RecentTask = {
    id: number;
    task_number: string;
    title: string;
    category: string;
    priority: string;
    status: string;
    sla_due_at: string | null;
    emails_count: number;
};

type RecentEmail = {
    id: number;
    task_id: number | null;
    sender_name: string | null;
    sender_email: string;
    subject: string;
    status: string;
    received_at: string | null;
};

type Props = {
    stats: {
        total: number;
        open: number;
        needs_review: number;
        in_progress: number;
        overdue: number;
    };
    by_status: CountRow[];
    by_priority: CountRow[];
    by_category: CountRow[];
    recent_tasks: RecentTask[];
    recent_emails: RecentEmail[];
};

const props = defineProps<Props>();

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const maxPriority = computed(() => Math.max(1, ...props.by_priority.map((row) => row.count)));

const emailStatusClass = (status: string) => {
    switch (status) {
        case 'analyzed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200';
        case 'failed':
            return 'bg-destructive/10 text-destructive';
        case 'ignored':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            description="Maintenance inbox overview — new tasks are created automatically from analyzed emails."
        />

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <Link href="/tasks">
                <Card class="h-full transition-colors hover:bg-muted/40">
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground uppercase">Total tasks</p>
                        <p class="mt-1 text-2xl font-semibold">{{ stats.total }}</p>
                    </CardContent>
                </Card>
            </Link>
            <Link href="/tasks?status=Open">
                <Card class="h-full transition-colors hover:bg-muted/40">
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground uppercase">Open</p>
                        <p class="mt-1 text-2xl font-semibold text-sky-600 dark:text-sky-400">{{ stats.open }}</p>
                    </CardContent>
                </Card>
            </Link>
            <Link href="/tasks?status=In%20Progress">
                <Card class="h-full transition-colors hover:bg-muted/40">
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground uppercase">In progress</p>
                        <p class="mt-1 text-2xl font-semibold text-orange-500">{{ stats.in_progress }}</p>
                    </CardContent>
                </Card>
            </Link>
            <Link href="/tasks?status=Needs%20Review">
                <Card class="h-full transition-colors hover:bg-muted/40">
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground uppercase">Needs review</p>
                        <p class="mt-1 text-2xl font-semibold text-purple-600 dark:text-purple-400">{{ stats.needs_review }}</p>
                    </CardContent>
                </Card>
            </Link>
            <Card class="border-destructive/40 h-full">
                <CardContent class="p-4">
                    <p class="text-xs text-muted-foreground uppercase">Overdue SLA</p>
                    <p class="mt-1 text-2xl font-semibold text-destructive">{{ stats.overdue }}</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardContent class="p-4">
                    <p class="mb-3 text-sm font-medium text-foreground">By priority</p>
                    <div class="flex flex-col gap-3">
                        <div v-for="row in by_priority" :key="row.key" class="flex items-center gap-3">
                            <div class="w-24 shrink-0">
                                <PriorityBadge :value="row.key" />
                            </div>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{ width: `${Math.round((row.count / maxPriority) * 100)}%` }"
                                />
                            </div>
                            <span class="w-8 text-right text-sm tabular-nums">{{ row.count }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <p class="mb-3 text-sm font-medium text-foreground">By status</p>
                    <div class="flex flex-col gap-3">
                        <div v-for="row in by_status" :key="row.key" class="flex items-center gap-3">
                            <div class="w-28 shrink-0">
                                <StatusBadge :value="row.key" />
                            </div>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{ width: `${Math.round((row.count / Math.max(1, ...by_status.map((r) => r.count))) * 100)}%` }"
                                />
                            </div>
                            <span class="w-8 text-right text-sm tabular-nums">{{ row.count }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <p class="mb-3 text-sm font-medium text-foreground">By category</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="row in by_category"
                            :key="row.key"
                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs"
                        >
                            {{ row.key }}
                            <span class="font-semibold tabular-nums">{{ row.count }}</span>
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <CardContent class="p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-medium text-foreground">Recent tasks</p>
                        <Link href="/tasks" class="text-xs text-primary hover:underline">View all</Link>
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50 text-xs text-muted-foreground uppercase">
                                    <th class="px-4 py-2.5 font-medium">Task</th>
                                    <th class="px-4 py-2.5 font-medium">Title</th>
                                    <th class="px-4 py-2.5 font-medium">Priority</th>
                                    <th class="px-4 py-2.5 font-medium">Status</th>
                                    <th class="px-4 py-2.5 font-medium">SLA due</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="task in recent_tasks"
                                    :key="task.id"
                                    class="border-b last:border-b-0 hover:bg-muted/40"
                                >
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <Link :href="`/tasks/${task.id}`" class="font-medium text-primary hover:underline">
                                            {{ task.task_number }}
                                        </Link>
                                    </td>
                                    <td class="max-w-xs truncate px-4 py-2.5">{{ task.title }}</td>
                                    <td class="px-4 py-2.5">
                                        <PriorityBadge :value="task.priority" />
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <StatusBadge :value="task.status" />
                                    </td>
                                    <td class="px-4 py-2.5 text-muted-foreground">{{ formatDate(task.sla_due_at) }}</td>
                                </tr>
                                <tr v-if="recent_tasks.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">No tasks yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <p class="mb-3 text-sm font-medium text-foreground">Latest emails analyzed</p>
                    <div class="flex flex-col divide-y">
                        <div v-for="email in recent_emails" :key="email.id" class="flex flex-col gap-1 py-2.5 first:pt-0">
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="inline-flex items-center rounded-md border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                                    :class="emailStatusClass(email.status)"
                                >
                                    {{ email.status }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ formatDate(email.received_at) }}</span>
                            </div>
                            <div class="truncate text-sm font-medium">
                                <Link v-if="email.task_id" :href="`/tasks/${email.task_id}`" class="hover:underline">
                                    {{ email.subject }}
                                </Link>
                                <template v-else>{{ email.subject }}</template>
                            </div>
                            <div class="truncate text-xs text-muted-foreground">
                                {{ email.sender_name ?? email.sender_email }}
                            </div>
                        </div>
                        <p v-if="recent_emails.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                            No emails analyzed yet.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>