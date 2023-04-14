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


class DistributionOfPopulationBySexAndMaritalStatus extends ApexChartWidget
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

    protected static ?int $sort = 1; 

    protected int | string | array $columnSpan = 'full';

    protected static bool $deferLoading = true;

    protected static ?string $pollingInterval = null;

    protected static string $chartId = 'distributionOfPopulationBySexAndMaritalStatus';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'I. Distribution Of Population by Marital Status and Sex';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    public function getPRMaritalAndSex($ageAndSex) {
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
                if(($data['q3'] === $ageAndSex[1]) && ($data['q8'] === $ageAndSex[0])){
                    $json_data_count += 1;
                }
            }
        }
        
        return $json_data_count;
    }

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
            ],
            'series' => [
                [
                    'name' => 'Male',
                    'data' => [ 
                        $this->getPRMaritalAndSex(['1', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['2', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['3', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['4', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['5', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['6', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPRMaritalAndSex(['7', '1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Female',
                    'data' => [ 
                        -$this->getPRMaritalAndSex(['1', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['2', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['3', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['4', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['5', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['6', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPRMaritalAndSex(['7', '2', $province, $city_or_municipality, $barangay]),
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => [
                    'Single',
                    'Married',
                    'Living-in',
                    'Widowed',
                    'Separated',
                    'Divorced',
                    'Unknown',
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
                    'text' => 'Marital Status',
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                    'barHeight' => '85%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => false,
                        ],
                        'position' => 'top'

                    ]
                ],
            ],
            'legend' => [
                'position' => 'top'
            ],
            'colors' => ['#4245db', '#db42ad'],
            'noData' => [
                'text' => 'Loading',
                'align' => 'center',
            ]
        ];
    }
}
