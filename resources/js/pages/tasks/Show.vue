<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import PriorityBadge from '@/components/maintenance/PriorityBadge.vue';
import StatusBadge from '@/components/maintenance/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TaskDetail } from '@/types/maintenance';

type Props = {
    task: TaskDetail;
    options: {
        statuses: string[];
        priorities: string[];
        categories: string[];
    };
};

const props = defineProps<Props>();

const form = ref({
    title: props.task.title,
    description: props.task.description ?? '',
    status: props.task.status,
    priority: props.task.priority,
    category: props.task.category,
    location_building: props.task.location.building ?? '',
    location_floor: props.task.location.floor ?? '',
    location_room: props.task.location.room ?? '',
    asset: props.task.asset ?? '',
});

const saving = ref(false);
const error = ref<string | null>(null);

const hasChanges = computed(() => {
    const f = form.value;
    return (
        f.title !== props.task.title ||
        f.description !== (props.task.description ?? '') ||
        f.status !== props.task.status ||
        f.priority !== props.task.priority ||
        f.category !== props.task.category ||
        f.location_building !== (props.task.location.building ?? '') ||
        f.location_floor !== (props.task.location.floor ?? '') ||
        f.location_room !== (props.task.location.room ?? '') ||
        f.asset !== (props.task.asset ?? '')
    );
});

const formatDate = (iso: string | null): string => {
    if (!iso) return '—';
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
};

const save = async () => {
    saving.value = true;
    error.value = null;
    try {
        const response = await fetch(`/api/tasks/${props.task.id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                ...form.value,
                description: form.value.description || null,
                location_building: form.value.location_building || null,
                location_floor: form.value.location_floor || null,
                location_room: form.value.location_room || null,
                asset: form.value.asset || null,
            }),
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const message = payload.message ?? payload.errors ?? 'Failed to save task.';
            throw new Error(typeof message === 'string' ? message : 'Failed to save task.');
        }

        toast.success(`${props.task.task_number} updated`);
        router.reload({ only: ['task'] });
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to save task.';
        toast.error(error.value);
    } finally {
        saving.value = false;
    }
};

const reprocess = async (emailId: number) => {
    const response = await fetch(`/api/emails/${emailId}/reprocess`, {
        method: 'POST',
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        toast.error('Failed to queue re-analysis.');
        return;
    }

    toast.success('Re-analysis queued');
};
</script>

<template>
    <Head :title="`Task ${task.task_number}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <Heading :title="`${task.task_number} — ${task.title}`" />
                <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <PriorityBadge :value="task.priority" />
                    <StatusBadge :value="task.status" />
                    <span>{{ task.category }}</span>
                    <span v-if="task.confidence !== null">· confidence {{ task.confidence.toFixed(2) }}</span>
                    <span>· created {{ formatDate(task.created_at) }}</span>
                    <span v-if="task.sla_due_at">· SLA due {{ formatDate(task.sla_due_at) }}</span>
                </div>
            </div>
            <Link href="/tasks" class="text-sm text-primary hover:underline">← Back to list</Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="flex flex-col gap-4 rounded-xl border p-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Original email</h2>

                <div class="flex flex-col gap-1 border-b pb-3">
                    <span class="text-base font-medium">{{ task.source_email?.subject }}</span>
                    <span class="text-sm text-muted-foreground">
                        {{ task.source_email?.sender_name ?? 'Unknown' }}
                        &lt;{{ task.source_email?.sender_email }}&gt;
                    </span>
                    <span class="text-xs text-muted-foreground">{{ formatDate(task.source_email?.received_at ?? null) }}</span>
                </div>

                <div class="grid gap-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        AI-extracted fields
                    </h3>
                    <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Category</dt>
                            <dd class="font-medium">{{ task.category }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Priority</dt>
                            <dd class="font-medium">{{ task.priority }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Location</dt>
                            <dd>
                                <span v-if="[task.location.building, task.location.floor, task.location.room].some(Boolean)">
                                    {{
                                        [task.location.building, task.location.floor, task.location.room]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </span>
                                <span v-else class="text-muted-foreground">Not found</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Asset</dt>
                            <dd>{{ task.asset ?? 'Not found' }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Plain-text body
                    </h3>
                    <pre
                        class="max-h-96 overflow-auto rounded-lg bg-muted/50 p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap"
                        >{{ task.source_email?.body_text }}</pre
                    >
                </div>

                <div v-if="task.emails.length > 1">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Every linked email ({{ task.emails.length }})
                    </h3>
                    <div class="flex flex-col gap-3">
                        <div
                            v-for="email in task.emails"
                            :key="email.id"
                            class="rounded-lg border p-3 text-sm"
                        >
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium">{{ email.subject }}</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7"
                                    :disabled="email.status === 'pending' || email.status === 'processing'"
                                    @click="reprocess(email.id)"
                                >
                                    Re-analyze
                                </Button>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ email.sender_name ?? 'Unknown' }} &lt;{{ email.sender_email }}&gt; ·
                                {{ formatDate(email.received_at) }} · status: {{ email.status }}
                            </div>
                            <pre class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap font-mono text-xs">{{ email.body_text }}</pre>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-col gap-6">
                <section class="rounded-xl border p-4">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Edit task
                    </h2>

                    <form class="grid gap-4" @submit.prevent="save">
                        <div class="grid gap-1.5">
                            <Label for="title">Title</Label>
                            <Input id="title" v-model="form.title" maxlength="80" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description">Description</Label>
                            <Input id="description" v-model="form.description" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-1.5">
                                <Label for="status">Status</Label>
                                <select id="status" v-model="form.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option v-for="value in options.statuses" :key="value" :value="value">{{ value }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="priority">Priority</Label>
                                <select id="priority" v-model="form.priority" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option v-for="value in options.priorities" :key="value" :value="value">{{ value }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="category">Category</Label>
                                <select id="category" v-model="form.category" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option v-for="value in options.categories" :key="value" :value="value">{{ value }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="grid gap-1.5">
                                <Label for="building">Building</Label>
                                <Input id="building" v-model="form.location_building" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="floor">Floor</Label>
                                <Input id="floor" v-model="form.location_floor" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="room">Room / Unit</Label>
                                <Input id="room" v-model="form.location_room" />
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="asset">Asset</Label>
                            <Input id="asset" v-model="form.asset" placeholder="e.g. FCU-12" />
                        </div>

                        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="saving || !hasChanges">Save changes</Button>
                            <span v-if="saving" class="text-sm text-muted-foreground">Saving…</span>
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border p-4">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Activity log
                    </h2>

                    <ol v-if="task.logs.length > 0" class="relative flex flex-col gap-4 border-l pl-4">
                        <li v-for="log in task.logs" :key="log.id" class="relative">
                            <span
                                class="absolute top-1.5 -left-[21.5px] h-2.5 w-2.5 rounded-full border-2 border-background"
                                :class="{
                                    'bg-emerald-500': log.type === 'first_report',
                                    'bg-amber-500': log.type === 'duplicate',
                                    'bg-sky-500': log.type === 'reply',
                                    'bg-muted-foreground': log.type === 'status_change' || log.type === 'field_change',
                                }"
                            />
                            <div class="text-sm">{{ log.message }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ log.sender ?? 'System' }}
                                <template v-if="log.sender_email"> &lt;{{ log.sender_email }}&gt;</template>
                                <span v-if="log.created_at"> · {{ formatDate(log.created_at) }}</span>
                            </div>
                        </li>
                    </ol>
                    <p v-else class="text-sm text-muted-foreground">No activity yet.</p>
                </section>
            </div>
        </div>
    </div>
</template>