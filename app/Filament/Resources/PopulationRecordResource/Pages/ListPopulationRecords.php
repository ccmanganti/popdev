<?php

namespace App\Filament\Resources\PopulationRecordResource\Pages;

use App\Filament\Resources\PopulationRecordResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPopulationRecords extends ListRecords
{
    protected static string $resource = PopulationRecordResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
