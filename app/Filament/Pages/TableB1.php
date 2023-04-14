<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DistributionOfHouseholdsByHouseOccupancyAndHouseholdSize;

class TableB1 extends Page
{
    protected static ?int $navigationSort = 12;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static function getActiveNavigationIcon(): string
    {
        return 'heroicon-s-home';
    }

    protected static string $view = 'filament.pages.table-b1';

    protected static ?string $navigationGroup = 'Household-Based Demographic Indicators';

    protected static ?string $title = 'Table I: Distribution of Households by House Occupancy and Household Size';
 
    protected static ?string $navigationLabel = 'Table I: Distribution of Households by House Occupancy and Household Size';
    
    protected static ?string $slug = 'table-household-i';

    protected static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->hasRole('Enumerator');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DistributionOfHouseholdsByHouseOccupancyAndHouseholdSize::class
        ];
    }
}