<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationBySexAndMaritalStatus;

class Table1 extends Page
{

    protected static ?int $navigationSort = 5;
    
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table1';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table I: Distribution of Population by Sex and Marital Status';
 
    protected static ?string $navigationLabel = 'Table I: Distribution of Population by Sex and Marital Status';
    
    protected static ?string $slug = 'table-i';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationBySexAndMaritalStatus::class
        ];
    }
}
