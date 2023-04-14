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


class DistributionOfPopulationByAgeAndSex extends ApexChartWidget
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

    protected static ?int $sort = 6; 

    protected static bool $deferLoading = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;
    
    protected static string $chartId = 'distributionOfPopulationByAgeAndSex';

    
    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'VI. Distribution of Population By Age and Sex';

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
            return [

            ];
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
            if($ageAndSex[3] == null && $ageAndSex[4] == null && $ageAndSex[5] == null){
                $json_data = PopulationRecord::all();
            } else if ($ageAndSex[3] != null && $ageAndSex[4] == null && $ageAndSex[5] == null){
                $json_data = PopulationRecord::where('province', '=', $ageAndSex[3])->get(); 
            } else if ($ageAndSex[3] != null && $ageAndSex[4] != null && $ageAndSex[5] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $ageAndSex[3])->get(); 
            } else if ($ageAndSex[3] != null && $ageAndSex[4] != null && $ageAndSex[5] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[5])->get(); 
            }

        } else if($user->hasRole('LGU')){
            if ($ageAndSex[3] != null && $ageAndSex[4] != null && $ageAndSex[5] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $user->city_or_municipality)->get(); 
            } else if ($ageAndSex[3] != null && $ageAndSex[4] != null && $ageAndSex[5] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[5])->get(); 
            }
        } else if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
        }

        foreach($json_data as $json){
            foreach($json->individual_record as $data){
                if(($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1])){
                    $json_data_count += 1;
                }
            }
        }

        // foreach($json_data as $json){
            
        //     // SHOW DATA DEPENING ON USER ACCESS AND ROLES
        //         foreach($json->individual_record as $data){
        //             if($user->hasRole('Superadmin')){
        //                 if(($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && $ageAndSex[3] == null && $ageAndSex[4] == null && $ageAndSex[5] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($json->province == $ageAndSex[3]) && $ageAndSex[4] == null && $ageAndSex[5] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($json->province == $ageAndSex[3]) && ($json->city_or_municipality == $ageAndSex[4]) && $ageAndSex[5] == null){
        //                     $json_data_count += 1;
        //                 } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($json->province == $ageAndSex[3]) && ($json->city_or_municipality == $ageAndSex[4]) && ($json->barangay == $ageAndSex[5])){
        //                     $json_data_count += 1;
        //                 }

        //             } else if($user->hasRole('LGU')){
        //                 if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality)){
        //                     if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($json->city_or_municipality == $ageAndSex[4]) && $ageAndSex[5] == null){
        //                         $json_data_count += 1;
        //                     } else if (($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1]) && ($json->city_or_municipality == $ageAndSex[4]) && ($json->barangay == $ageAndSex[5])){
        //                         $json_data_count += 1;
        //                     }
        //                 }
        //             } else if($user->hasRole('Enumerator') || $user->hasRole('Barangay')){
        //                 if((($json->province) == $user->province) && (($json->city_or_municipality) == $user->city_or_municipality) && (($json->barangay) == $user->barangay)){
        //                     if(($data['q3'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1])){
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
                'height' => 400,
                'stacked' => true,
                'zoom' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Male Population by Age',
                    'data' => [
                        $this->getPRHHAndSex([0, 4,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([5, 9,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([10, 14,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([15, 19,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([20, 24,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([25, 29,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([30, 34,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([35, 39,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([40, 44,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([45, 49,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([50, 54,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([55, 59,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([60, 64,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([65, 69,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([70, 74,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([75, 79,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRHHAndSex([80, 150,'1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Female Population by Age',
                    'data' => [
                        -$this->getPRHHAndSex([0, 4,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([5, 9,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([10, 14,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([15, 19,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([20, 24,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([25, 29,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([30, 34,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([35, 39,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([40, 44,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([45, 49,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([50, 54,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([55, 59,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([60, 64,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([65, 69,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([70, 74,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([75, 79,'2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRHHAndSex([80, 150,'2', $province, $city_or_municipality, $barangay]),
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => [
                    '00-04', 
                    '05-09', 
                    '10-14', 
                    '15-19',
                    '20-24',
                    '25-29',
                    '30-34',
                    '35-39',
                    '40-44',
                    '45-49',
                    '50-54',
                    '55-59',
                    '60-64',
                    '65-69',
                    '70-74',
                    '75-79',
                    '80+',
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
                    'text' => 'Age Range'
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
