<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByReligionAndSex;

class Table4 extends Page
{
    protected static ?int $navigationSort = 8;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table4';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table IV: Distribution of Population by Religion and Sex';
 
    protected static ?string $navigationLabel = 'Table IV: Distribution of Population by Religion and Sex';
    
    protected static ?string $slug = 'table-iv';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByReligionAndSex::class
        ];
    }
}
