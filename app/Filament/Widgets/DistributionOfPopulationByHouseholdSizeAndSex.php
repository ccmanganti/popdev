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



class DistributionOfPopulationByHouseholdSizeAndSex extends ApexChartWidget
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

    protected static ?int $sort = 2;

    protected static bool $deferLoading = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;
    
    protected static string $chartId = 'distributionOfPopulationByHouseholdSizeAndSex';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'II. Distribution of Population By HH Size and Sex';

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
    
    

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

     public function getPRHHAndSex($ageAndSex) {
        $user = auth()->user();
        $json_data_count = 0;

        if($user->hasRole('Superadmin')){
            if($ageAndSex[2] == null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::all();
            } else if ($ageAndSex[2] != null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('province', '=', $ageAndSex[2])->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $ageAndSex[3])->get(); 
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
            foreach($json->individual_record as $data){
                if(($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0])){
                    $json_data_count += 1;
                }
            }
        }

        // foreach($json_data as $json){
            
        //     // SHOW DATA DEPENING ON USER ACCESS AND ROLES
        //         foreach($json->individual_record as $data){
                
        //             if($user->hasRole('Superadmin')){
        //                 if(($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && $ageAndSex[2] == null && $ageAndSex[3] == null && $ageAndSex[4] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && ($json->province == $ageAndSex[2]) && $ageAndSex[3] == null && $ageAndSex[4] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && ($json->province == $ageAndSex[2]) && ($json->city_or_municipality == $ageAndSex[3]) && $ageAndSex[4] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && ($json->province == $ageAndSex[2]) && ($json->city_or_municipality == $ageAndSex[3]) && ($json->barangay == $ageAndSex[4])){
        //                     $json_data_count += 1;
        //                 }

        //             } else if($user->hasRole('LGU')){
        //                 if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality)){
        //                     if (($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && ($json->city_or_municipality == $ageAndSex[3]) && $ageAndSex[4] == null){
        //                         $json_data_count += 1;
        //                     } else if (($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0]) && ($json->city_or_municipality == $ageAndSex[3]) && ($json->barangay == $ageAndSex[4])){
        //                         $json_data_count += 1;
        //                     }
        //                 }
        //             } else if($user->hasRole('Enumerator') || $user->hasRole('Barangay')){
        //                 if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality) && (($json->barangay) == $user->barangay)){
        //                     if(($data['q3'] === $ageAndSex[1]) && ((int)$json->household_members_total == $ageAndSex[0])){
        //                         $json_data_count += 1;
        //                     }
        //                 }
        //             }
        //     }
        // }
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
                'height' => 300,
                'stacked' => true,
                'zoom' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Male Population by Household Size',
                    'data' => [
                        $this->getPRHHAndSex([1, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([2, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([3, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([4, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([5, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([6, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([7, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([8, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([9, '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([10, '1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Female Population by Household Size',
                    'data' => [
                        -$this->getPRHHAndSex([1, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([2, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([3, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([4, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([5, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([6, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([7, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([8, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([9, '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([10, '2', $province, $city_or_municipality, $barangay]),
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
                // 'min' => -$this->getPRHHAndSex(null),
                // 'max' => $this->getPRHHAndSex(null),
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
            'colors' => ['#4245db', '#db42ad'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                    'barHeight' => '85%',
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
                // 'offsetX' => 30
            ],
            'grid' => [
                'borderColor' => '#919191',
                'strokeDashArray' => 2,
                'xaxis' => [
                    'lines' => [
                        'show' => false
                    ]
                ]
            ],
            'legend' => [
                'position' => 'top',
            ],
        ];
    }
}
