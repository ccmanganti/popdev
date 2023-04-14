<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByAgeAndMaritalStatus;


class Table3 extends Page
{
    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table3';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table III: Distribution of Population by Age and Marital Status';
 
    protected static ?string $navigationLabel = 'Table III: Distribution of Population by Age and Marital Status';
    
    protected static ?string $slug = 'table-iii';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByAgeAndMaritalStatus::class
        ];
    }
}
