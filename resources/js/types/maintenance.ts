export type TaskLocation = {
    building: string | null;
    floor: string | null;
    room: string | null;
};

export type TaskRequester = {
    name: string | null;
    email: string | null;
};

export interface TaskListItem {
    id: number;
    task_number: string;
    title: string;
    category: string;
    priority: string;
    status: string;
    location: TaskLocation;
    asset: string | null;
    confidence: number | null;
    requester: TaskRequester;
    emails_count: number;
    sla_due_at: string | null;
    created_at: string | null;
}

export interface TaskEmail {
    id: number;
    message_id: string;
    in_reply_to: string | null;
    sender_name: string | null;
    sender_email: string;
    subject: string;
    body_text: string;
    attachments: string[];
    received_at: string | null;
    status: string;
    analysis: Record<string, unknown> | null;
}

export interface TaskLogEntry {
    id: number;
    type: 'first_report' | 'duplicate' | 'reply' | 'status_change' | 'field_change';
    message: string;
    sender: string | null;
    sender_email: string | null;
    created_at: string | null;
}

export interface TaskDetail {
    id: number;
    task_number: string;
    title: string;
    description: string | null;
    category: string;
    priority: string;
    status: string;
    location: TaskLocation;
    asset: string | null;
    confidence: number | null;
    requester: TaskRequester;
    sla_due_at: string | null;
    created_at: string | null;
    source_email: TaskEmail | null;
    emails: TaskEmail[];
    logs: TaskLogEntry[];
}