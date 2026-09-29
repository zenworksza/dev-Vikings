<?php

namespace App\Filament\Portal\Resources\Locations\Pages;

use App\Filament\Portal\Resources\Locations\LocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ownership always comes from the session, never from the form.
        $data['user_id'] = auth()->id();

        return $data;
    }
}
