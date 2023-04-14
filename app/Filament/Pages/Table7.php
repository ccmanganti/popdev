<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByEmploymentStatusAndSex;


class Table7 extends Page
{
    protected static ?int $navigationSort = 11;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table7';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table VII: Distribution of Population by Employment Status and Sex';
 
    protected static ?string $navigationLabel = 'Table VII: Distribution of Population by Employment Status and Sex';
    
    protected static ?string $slug = 'table-vii';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByEmploymentStatusAndSex::class
        ];
    }
}
