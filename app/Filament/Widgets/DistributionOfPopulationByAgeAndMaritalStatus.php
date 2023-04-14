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


class DistributionOfPopulationByAgeAndMaritalStatus extends ApexChartWidget
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

    protected static ?int $sort = 3; 

    protected static bool $deferLoading = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static string $chartId = 'distributionOfPopulationByAgeAndMaritalStatus';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'III. Distribution of Population by Age and Marital Status';

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


    public function getPRMaritalAndAge($ageAndSex) {
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
                if(($data['q8'] === $ageAndSex[2]) && ((int)$data['q4'] >= $ageAndSex[0]) && ((int)$data['q4'] <= $ageAndSex[1])){
                    $json_data_count += 1;
                }
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
                'height' => 300,
                'zoom' => [
                    'enabled' => true,
                ],
                'stacked' => true,
            ],
            'series' => [
                [
                    'name' => 'Single',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Married',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'2', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'2', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Living-in',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'3', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'3', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Widowed',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'4', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'4', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Separated',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'5', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'5', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Divorced',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'6', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'6', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Unknown',
                    'data' => [
                        $this->getPRMaritalAndAge([0, 4,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([5, 9,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([10, 14,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([15, 19,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([20, 24,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([25, 29,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([30, 34,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([35, 39,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([40, 44,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([45, 49,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([50, 54,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([55, 59,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([60, 64,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([65, 69,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([70, 74,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([75, 79,'7', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndAge([80, 150,'7', $province, $city_or_municipality, $barangay]),
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
                    'text' => 'Age Range',
                    'offsetY' => 95
                ],
                'tickPlacement' => 'on'
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
                'title' => [
                    'text' => 'Population',
                ],
                
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false,
                    'barWidth' => '85%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => true,
                            ],
                    ]
                ],
            ],
            'legend' => [
                'position' => 'top'
            ],
            
            'colors' => ['#4245db', '#42db4c', '#db7842', '#42d1db', '#db42ad', '#a3db42', '#828282'],
        ];
    }
}
