<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByHighestEducationalAttainmentAndSex;


class Table8 extends Page
{
    protected static ?int $navigationSort = 12;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table8';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table VIII: Distribution of Population by Highest Educational Attainment and Sex';
 
    protected static ?string $navigationLabel = 'Table VIII: Distribution of Population by Highest Educational Attainment and Sex';
    
    protected static ?string $slug = 'table-viii';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByHighestEducationalAttainmentAndSex::class
        ];
    }
}
