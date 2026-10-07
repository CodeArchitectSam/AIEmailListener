<x-mail::message>
# Maintenance request received

Hi @if($task->requester_name){{ $task->requester_name }}@endif,

We have registered your request as **{{ $task->task_number }}**.

- **Issue:** {{ $task->title }}
- **Category:** {{ $task->category }}
- **Priority:** {{ $task->priority }}
@if($task->location_building || $task->location_floor || $task->location_room)- **Location:** @if($task->location_building){{ $task->location_building }} @endif @if($task->location_floor){{ $task->location_floor }} @endif @if($task->location_room){{ $task->location_room }}@endif
@endif
- **Estimated response:** {{ $task->sla_due_at?->format('D, j M Y H:i') ?? 'as soon as possible' }}

@if($severity === 'needs-review')
Our team will review this request and confirm the details shortly.
@else
Our maintenance team has been notified and will follow up.
@endif

Thanks,<br>
{{ config('mail.from.name') }}
</x-mail::message>