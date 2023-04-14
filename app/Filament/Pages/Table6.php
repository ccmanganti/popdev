<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfPopulationByAgeAndSex;

class Table6 extends Page
{
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.pages.table6';

    protected static ?string $navigationGroup = 'Basic Demographic Indicators';

    protected static ?string $title = 'Table VI: Distribution of Population by Age and Sex';
 
    protected static ?string $navigationLabel = 'Table VI: Distribution of Population by Age and Sex';
    
    protected static ?string $slug = 'table-vi';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfPopulationByAgeAndSex::class
        ];
    }
}
