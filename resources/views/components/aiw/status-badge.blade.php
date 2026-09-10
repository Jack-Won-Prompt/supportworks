@props(['status'])

@php
    $s = $status instanceof \App\Enums\AiWork\AiwJobStatus
        ? $status
        : \App\Enums\AiWork\AiwJobStatus::from((string) $status);

    $tone = match ($s) {
        \App\Enums\AiWork\AiwJobStatus::Queued,
        \App\Enums\AiWork\AiwJobStatus::Dispatched        => 'bg-gray-100 text-gray-600',
        \App\Enums\AiWork\AiwJobStatus::Running           => 'bg-blue-50 text-blue-700',
        \App\Enums\AiWork\AiwJobStatus::WaitingInput      => 'bg-amber-50 text-amber-700',
        \App\Enums\AiWork\AiwJobStatus::WaitingPermission => 'bg-orange-50 text-orange-700',
        \App\Enums\AiWork\AiwJobStatus::Handover          => 'bg-violet-50 text-violet-700',
        \App\Enums\AiWork\AiwJobStatus::Completed         => 'bg-emerald-50 text-emerald-700',
        \App\Enums\AiWork\AiwJobStatus::Failed            => 'bg-red-50 text-red-700',
        \App\Enums\AiWork\AiwJobStatus::Cancelled         => 'bg-gray-100 text-gray-500',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-block rounded px-1.5 py-0.5 text-[11px] font-medium {$tone}"]) }}
      data-aiw-status>{{ $s->label() }}</span>
