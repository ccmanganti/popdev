<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\PopulationRecord; 
use App\Models\PhilProvince; 
use App\Models\PhilMuni; 
use App\Models\Philbrgy; 
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Closure;
use Illuminate\Support\Facades\Route;

class DistributionOfHouseholdsByHouseOccupancyAndHouseholdSize extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */

    public static function canView(): bool
    {
        if ($currentPath= Route::getFacadeRoot()->current()->uri() == "/"){
            return false;
        } else {
            return true;
        }
    }
 
    protected static bool $deferLoading = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static string $chartId = 'distributionOfHouseholdsByHouseOccupancyAndHouseholdSize';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'I. Distribution of Households by House Occupancy and Household Size';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */


    protected function getFormSchema(): array
    {
        $user = auth()->user();
        if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            return [];
        }
        return [
            Select::make('province')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                    } else {
                        return false;
                    }
                })
                ->label('Province Name')
                ->options(function() use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else {
                        return Philprovince::all()->pluck('provDesc', 'provCode');
                    }
                }),
            Select::make('city_or_municipality')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                    } else {
                        return false;
                    }   
                })
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->label('City/Municipality Name')
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else {
                        return Philmuni::where('provCode', '=', $get('province'))->pluck('citymunDesc', 'citymunCode');
                    }
                }),
            Select::make('barangay')
                ->reactive()
                ->label('Barangay Name')
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philbrgy::where('citymunCode', '=', $user->city_or_municipality)->pluck('brgyDesc', 'brgyCode'); 
                    } else {
                        return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                    }
                })
        ];
    }

    public function getHHHouseAndSize($ageAndSex) {
        $user = auth()->user();
        $json_data_count = 0;

        if($user->hasRole('Superadmin')){
            if($ageAndSex[2] == null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::all();
            } else if ($ageAndSex[2] != null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('province', '=', $ageAndSex[2])->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $ageAndSex[2])->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[4])->get(); 
            }

        } else if($user->hasRole('LGU')){
            if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $user->city_or_municipality)->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[4])->get(); 
            }
        } else if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
        }

        foreach($json_data as $json){
            if(($json['q25'] === $ageAndSex[1] && $json['household_members_total'] == $ageAndSex[0] && (int)$json['household_members_total'] <= 9)){
                $json_data_count += 1;
            } else if(($json['q25'] === $ageAndSex[1] && $json['household_members_total'] == $ageAndSex[0] && (int)$json['household_members_total'] >= 10)){
                $json_data_count += 1;
            }
        }
        return $json_data_count;
    }
    
    protected function getOptions(): array
    {

        if (auth()->user()->hasRole('Barangay') || auth()->user()->hasRole('Enumerator')){
            $province = null;
            $city_or_municipality = null;
            $barangay = null;
            
        } else {
            $activeFilter = $this->filter;
            $province = $this->filterFormData['province'];
            $city_or_municipality = $this->filterFormData['city_or_municipality'];
            $barangay = $this->filterFormData['barangay'];    
        }

        if (!$this->readyToLoad) {
            return [];
        }
        
        sleep(1);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 500,
                'zoom' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Total Owned Households of HH Size',
                    'data' => [
                        $this->getHHHouseAndSize(['1','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['2','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['3','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['4','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['5','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['6','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['7','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['8','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['9','1', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['10','1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Total Rented Households of HH Size',
                    'data' => [
                        $this->getHHHouseAndSize(['1','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['2','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['3','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['4','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['5','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['6','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['7','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['8','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['9','2', $province, $city_or_municipality, $barangay]),
                        $this->getHHHouseAndSize(['10','2', $province, $city_or_municipality, $barangay]),
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => [
                    '1', 
                    '2', 
                    '3', 
                    '4',
                    '5',
                    '6',
                    '7',
                    '8',
                    '9',
                    '10+',
                ],
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                    
                ],
                'title' => [
                    'text' => 'Population Comparison',
                ],
                'tickPlacement' => 'on',
            ],
            'yaxis' => [
                // 'min' => -$this->getHHHouseAndSize(null),
                // 'max' => $this->getHHHouseAndSize(null),
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
                'title' => [
                    'text' => 'Household Size'
                ],

            ],
            'colors' => ['#21b842', '#b84e21'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                    'barHeight' => '75%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => false,
                            'style' => [
                                'color' => 'gray'
                            ]
                            ],
                            'position' => 'top'
                    ]
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'colors' => ['white']
                ],
            ],
            'grid' => [
                'borderColor' => '#919191',
                'strokeDashArray' => 2,
                'xaxis' => [
                    'lines' => [
                        'show' => false
                    ]
                    ],
            ],
            'legend' => [
                'position' => 'top',
            ],
            'tooltip'=> [
                'enabled' => true,
                'y' => [
                    'formatter' => function($val){
                        return $val.'Hi';
                    }
                ]
            ]
        ];
    }
}
