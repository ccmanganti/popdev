<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByEthnicityAndSex;


class Table5 extends Page
{
    protected static ?int $navigationSort = 9;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table5';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table V: Distribution of Population by Ethnicity and Sex';
 
    protected static ?string $navigationLabel = 'Table V: Distribution of Population by Ethnicity and Sex';
    
    protected static ?string $slug = 'table-v';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByEthnicityAndSex::class
        ];
    }
}
