@php
    $documents = $getLivewire()->application->documents()->latest('id')->get();
@endphp

<div class="space-y-2">
    <h3 class="text-sm font-semibold">Uploaded documents</h3>

    @forelse ($documents as $document)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10">
            <div>
                <div class="font-medium">{{ $document->original_name }}</div>
                <div class="text-xs text-gray-500">
                    {{ \App\Enums\DocumentType::tryFrom($document->type)?->label() ?? $document->type }}
                    · {{ $document->status->label() }}
                    @if ($document->review_note) · {{ $document->review_note }} @endif
                </div>
            </div>
            <button type="button" wire:click="deleteDocument({{ $document->id }})"
                    wire:confirm="Remove this document?"
                    class="text-sm text-danger-600 hover:underline">Remove</button>
        </div>
    @empty
        <p class="text-sm text-gray-500">Nothing uploaded yet.</p>
    @endforelse
</div>
