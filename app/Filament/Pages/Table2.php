<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByHouseholdSizeAndSex;


class Table2 extends Page
{
    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table2';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table II: Distribution of Population by Household Size and Sex';
 
    protected static ?string $navigationLabel = 'Table II: Distribution of Population by Household Size and Sex';
    
    protected static ?string $slug = 'table-ii';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByHouseholdSizeAndSex::class
        ];
    }
}
