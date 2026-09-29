<x-filament-panels::page>
    @if ($this->canEdit())
        @if ($this->isChangesRequested())
            <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-600 dark:bg-warning-950 dark:text-warning-200">
                <strong>Changes requested.</strong>
                {{ $this->application->decision_reason }}
                Update your application below and resubmit it.
            </div>
        @endif

        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    @else
        <x-filament::section>
            <x-slot name="heading">Application {{ strtolower($this->statusLabel()) }}</x-slot>

            @switch($this->application->status)
                @case(\App\Enums\ApplicationStatus::Approved)
                    <p>Congratulations — your application has been approved. You now have franchisee access.</p>
                    <p class="mt-3">
                        <x-filament::button tag="a" :href="\App\Filament\Portal\Resources\Locations\LocationResource::getUrl('index')">
                            Add or manage your locations
                        </x-filament::button>
                    </p>
                    @break
                @case(\App\Enums\ApplicationStatus::Rejected)
                    <p>After review we are unable to proceed with your application.</p>
                    @if ($this->application->decision_reason)
                        <p class="mt-2 text-sm text-gray-500">{{ $this->application->decision_reason }}</p>
                    @endif
                    @break
                @default
                    <p>Thanks — your application was submitted{{ $this->application->submitted_at ? ' on '.$this->application->submitted_at->format('j F Y') : '' }} and is being reviewed. We will email you as soon as there is an update.</p>
            @endswitch
        </x-filament::section>
    @endif
</x-filament-panels::page>
