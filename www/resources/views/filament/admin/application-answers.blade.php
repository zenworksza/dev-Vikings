@php
    $rows = \App\Support\ApplicationAnswers::rows($getRecord()->data);
@endphp

@if ($rows === [])
    <p class="text-sm text-gray-500">No answers yet.</p>
@else
    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
        @foreach ($rows as $label => $value)
            <div>
                <dt class="text-xs text-gray-500">{{ $label }}</dt>
                <dd class="whitespace-pre-line">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
@endif
